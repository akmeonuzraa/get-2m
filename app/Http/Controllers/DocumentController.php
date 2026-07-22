<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Models\Document;
use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\Mime\MimeTypes;
use Throwable;

class DocumentController extends BaseCrudController
{
    protected string $modelClass = Document::class;

    /**
     * Filtre les documents visibles selon le rôle de l'utilisateur.
     */
    protected function baseQuery(Request $request)
    {
        $user = $request->user();
        $query = Document::query()->where('status', 'active');

        if ($user->isAdmin()) {
            return $query; // Accès à tout
        }

        if ($user->isResponsable()) {
            return $query->where('service', $user->service); // Son service
        }

        // Utilisateur simple : ses propres documents + ceux de ses espaces
        $spaceIds = $user->spaces()->pluck('spaces.id');
        return $query->where(function ($q) use ($user, $spaceIds) {
            $q->where('uploaded_by', $user->id)
              ->orWhereIn('space_id', $spaceIds);
        });
    }

    protected function storeRules(): array
    {
        return [
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'file'        => 'required|file|max:20480|mimes:pdf,docx,xlsx,pptx,txt,jpg,jpeg,png,gif,zip', // 20 Mo, whitelist extensions
            'space_id'    => 'nullable|exists:spaces,id',
            'folder_id'   => 'nullable|exists:folders,id',
            'keywords'    => 'nullable|array',
        ];
    }

    protected function updateRules(): array
    {
        return [
            'title'       => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'keywords'    => 'nullable|array',
        ];
    }

    /**
     * Valide le MIME-type réel du fichier uploadé via finfo + Symfony MimeTypes.
     * Retourne true si le type réel du fichier correspond à une extension whitelistée.
     */
    protected function validateRealMimeType(\Illuminate\Http\UploadedFile $file): bool
    {
        // Whitelisted extensions and their accepted MIME types
        $allowedMimes = [
            'pdf'  => ['application/pdf'],
            'txt'  => ['text/plain'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
            'jpg'  => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png'  => ['image/png'],
            'gif'  => ['image/gif'],
            'zip'  => ['application/zip', 'application/x-zip-compressed'],
        ];

        $extension = strtolower($file->getClientOriginalExtension());
        
        // Check if extension is whitelisted
        if (!isset($allowedMimes[$extension])) {
            return false;
        }

        // Get real MIME type using finfo (most reliable)
        $realMimeType = @finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file->getRealPath());
        
        if (!$realMimeType) {
            // Fallback to Symfony MIME types if finfo fails
            $mimeTypes = new MimeTypes();
            $realMimeType = $mimeTypes->guessMimeType($file->getRealPath());
        }

        if (!$realMimeType) {
            return false;
        }

        // Check if real MIME type matches whitelisted types for this extension
        return in_array($realMimeType, $allowedMimes[$extension], true);
    }

    /**
     * Surcharge store() pour gérer l'upload de fichier + création
     * automatique d'une version 1 dans document_versions.
     * Appelle AiService pour la classification du texte si disponible.
     */
    public function store(Request $request): JsonResponse
    {
        $formRequest = StoreDocumentRequest::createFrom($request);
        $formRequest->setContainer(app())->setRedirector(app('redirect'));
        $formRequest->setUserResolver($request->getUserResolver());
        $formRequest->setRouteResolver($request->getRouteResolver());

        if (! $formRequest->authorize()) {
            abort(403, 'Vous n\'êtes pas membre de cet espace.');
        }

        $user = $request->user();

        $validated = $request->validate($formRequest->rules());
        $file = $request->file('file');

        // Validate real MIME type to prevent spoofed files
        if (!$this->validateRealMimeType($file)) {
            return response()->json([
                'message' => 'Le type de fichier réel ne correspond pas à l\'extension fournie.',
                'error' => 'invalid_file_type'
            ], 422);
        }

        $path = $file->store('documents');

        // Prepare keywords from classification or use provided ones
        $keywords = $validated['keywords'] ?? [];

        $document = Document::create([
            'title'              => $validated['title'],
            'description'        => $validated['description'] ?? null,
            'file_path'          => $path,
            'original_filename'  => $file->getClientOriginalName(),
            'file_type'          => $file->getClientOriginalExtension(),
            'mime_type'          => $file->getMimeType(),
            'file_size'          => $file->getSize(),
            'keywords'           => $keywords,
            'service'            => $user->service,
            'space_id'           => $validated['space_id'] ?? null,
            'folder_id'          => $validated['folder_id'] ?? null,
            'uploaded_by'        => $user->id,
        ]);

        $document->versions()->create([
            'file_path'         => $path,
            'original_filename' => $file->getClientOriginalName(),
            'file_size'         => $file->getSize(),
            'version_number'    => 1,
            'uploaded_by'       => $user->id,
        ]);

        // Try to classify document (non-blocking)
        $this->classifyDocumentAsync($document);

        return response()->json($document, 201);
    }

    /**
     * Classify document using AiService (non-blocking, silently fails if unavailable).
     */
    protected function classifyDocumentAsync(Document $document): void
    {
        try {
            $aiService = new AiService();

            // For now, extract minimal text from filename for classification
            // In production, this would extract text from the file itself (PDF, DOCX, etc.)
            $textToClassify = $document->original_filename . ' ' . ($document->description ?? '');

            $result = $aiService->classify($textToClassify);

            if ($result && isset($result['tags']) && is_array($result['tags'])) {
                // Update keywords with AI classification results
                $currentKeywords = $document->keywords ?? [];
                if (!is_array($currentKeywords)) {
                    $currentKeywords = [];
                }

                // Merge AI tags with existing keywords
                $mergedKeywords = array_unique(array_merge($currentKeywords, $result['tags']));
                $document->update(['keywords' => array_values($mergedKeywords)]);
            }
        } catch (Throwable $e) {
            // Silently ignore any errors - the document is already created
            \Log::debug('Document classification failed', ['error' => $e->getMessage()]);
        }
    }
}