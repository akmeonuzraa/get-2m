"use client";

import { useState } from "react";
import {
  FileText,
  FileSpreadsheet,
  File,
  Eye,
  Download,
  Trash2,
  Pencil,
  Plus,
  Search,
  FolderOpen,
  FolderPlus,
  X,
  Share2,
  RotateCcw,
  Archive,
} from "lucide-react";
import { documents as initialDocuments, dossiers as initialDossiers, DocumentItem } from "@/data/documents";

const PAGE_SIZE = 5;

function getFileIcon(type: string) {
  if (type === "PDF") return <FileText className="w-5 h-5 text-red-600" />;
  if (type === "XLSX") return <FileSpreadsheet className="w-5 h-5 text-green-600" />;
  return <File className="w-5 h-5 text-blue-600" />;
}

function getBadgeClasses(type: string) {
  if (type === "PDF") return "bg-red-100 text-red-700";
  if (type === "DOCX") return "bg-blue-100 text-blue-700";
  return "bg-green-100 text-green-700";
}

function todayFr() {
  const d = new Date();
  return `${String(d.getDate()).padStart(2, "0")}/${String(d.getMonth() + 1).padStart(2, "0")}/${d.getFullYear()}`;
}

export default function GedPage() {
  const [documents, setDocuments] = useState<DocumentItem[]>(initialDocuments);
  const [dossiersList, setDossiersList] = useState<string[]>(initialDossiers);
  const [search, setSearch] = useState("");
  const [typeFilter, setTypeFilter] = useState("Tous");
  const [dossierFilter, setDossierFilter] = useState("Tous");
  const [sortBy, setSortBy] = useState("date");
  const [page, setPage] = useState(1);
  const [tab, setTab] = useState<"documents" | "corbeille">("documents");

  const [showAddModal, setShowAddModal] = useState(false);
  const [addForm, setAddForm] = useState({ nom: "", description: "", type: "PDF", dossier: "Rapports" });

  const [showFolderModal, setShowFolderModal] = useState(false);
  const [newFolderName, setNewFolderName] = useState("");

  const [confirmDeleteId, setConfirmDeleteId] = useState<number | null>(null);
  const [detailDoc, setDetailDoc] = useState<DocumentItem | null>(null);
  const [shareDoc, setShareDoc] = useState<DocumentItem | null>(null);
  const [editDoc, setEditDoc] = useState<DocumentItem | null>(null);
  const [editForm, setEditForm] = useState({ nom: "", description: "", dossier: "" });
  const [selected, setSelected] = useState<number[]>([]);

  const base = documents.filter((d) => (tab === "documents" ? !d.supprime : d.supprime));

  const filtered = base
    .filter((doc) => doc.nom.toLowerCase().includes(search.toLowerCase()))
    .filter((doc) => typeFilter === "Tous" || doc.type === typeFilter)
    .filter((doc) => dossierFilter === "Tous" || doc.dossier === dossierFilter)
    .sort((a, b) => (sortBy === "nom" ? a.nom.localeCompare(b.nom) : b.date.localeCompare(a.date)));

  const totalPages = Math.max(1, Math.ceil(filtered.length / PAGE_SIZE));
  const paginated = filtered.slice((page - 1) * PAGE_SIZE, page * PAGE_SIZE);

  function handleDelete(id: number) {
    setDocuments((docs) => docs.map((d) => (d.id === id ? { ...d, supprime: true } : d)));
    setConfirmDeleteId(null);
  }

  function handleRestore(id: number) {
    setDocuments((docs) => docs.map((d) => (d.id === id ? { ...d, supprime: false } : d)));
  }

  function toggleSelect(id: number) {
    setSelected((s) => (s.includes(id) ? s.filter((x) => x !== id) : [...s, id]));
  }

  function handleZipDownload() {
    alert(`Telechargement de ${selected.length} document(s) en ZIP (simulation)`);
    setSelected([]);
  }

  function openEdit(doc: DocumentItem) {
    setEditDoc(doc);
    setEditForm({ nom: doc.nom, description: doc.description, dossier: doc.dossier });
  }

  function saveEdit() {
    if (!editDoc) return;
    setDocuments((docs) =>
      docs.map((d) =>
        d.id === editDoc.id
          ? { ...d, nom: editForm.nom, description: editForm.description, dossier: editForm.dossier }
          : d
      )
    );
    setEditDoc(null);
  }

  function handleAddDocument() {
    if (!addForm.nom.trim()) {
      alert("Le nom du document est obligatoire.");
      return;
    }
    const newDoc: DocumentItem = {
      id: Math.max(0, ...documents.map((d) => d.id)) + 1,
      nom: addForm.nom,
      type: addForm.type as "PDF" | "DOCX" | "XLSX",
      auteur: "nouhaila",
      date: todayFr(),
      taille: "0 KB",
      dossier: addForm.dossier,
      description: addForm.description,
      supprime: false,
      versions: [{ numero: "v1", date: todayFr(), auteur: "nouhaila" }],
    };
    setDocuments((docs) => [newDoc, ...docs]);
    setAddForm({ nom: "", description: "", type: "PDF", dossier: dossiersList[1] || "Rapports" });
    setShowAddModal(false);
    setTab("documents");
    setPage(1);
  }

  function handleAddFolder() {
    if (!newFolderName.trim()) return;
    if (dossiersList.includes(newFolderName)) {
      alert("Ce dossier existe deja.");
      return;
    }
    setDossiersList((list) => [...list, newFolderName]);
    setNewFolderName("");
    setShowFolderModal(false);
  }

  function handleAddVersion() {
    if (!detailDoc) return;
    const nextNum = detailDoc.versions.length + 1;
    const updatedDoc = {
      ...detailDoc,
      date: todayFr(),
      versions: [{ numero: `v${nextNum}`, date: todayFr(), auteur: "nouhaila" }, ...detailDoc.versions],
    };
    setDocuments((docs) => docs.map((d) => (d.id === detailDoc.id ? updatedDoc : d)));
    setDetailDoc(updatedDoc);
  }

  return (
    <main className="p-8">
      <div className="flex justify-between items-center mb-2">
        <div>
          <h1 className="text-3xl font-bold">Gestion Électronique des Documents</h1>
          <p className="text-gray-600">
            {filtered.length} document{filtered.length > 1 ? "s" : ""} disponible{filtered.length > 1 ? "s" : ""}
          </p>
        </div>
        <div className="flex gap-2">
          <button
            onClick={() => setShowFolderModal(true)}
            className="bg-white border px-4 py-2 rounded-lg hover:bg-gray-50 flex items-center gap-2"
          >
            <FolderPlus className="w-4 h-4" />
            Nouveau dossier
          </button>
          <button
            onClick={() => setShowAddModal(true)}
            className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center gap-2"
          >
            <Plus className="w-4 h-4" />
            Ajouter un document
          </button>
        </div>
      </div>

      <div className="flex gap-2 mt-4 mb-6 border-b">
        <button
          onClick={() => { setTab("documents"); setPage(1); }}
          className={`px-4 py-2 text-sm font-medium ${tab === "documents" ? "border-b-2 border-blue-600 text-blue-600" : "text-gray-500"}`}
        >
          Documents
        </button>
        <button
          onClick={() => { setTab("corbeille"); setPage(1); }}
          className={`px-4 py-2 text-sm font-medium flex items-center gap-1 ${tab === "corbeille" ? "border-b-2 border-blue-600 text-blue-600" : "text-gray-500"}`}
        >
          <Archive className="w-4 h-4" />
          Corbeille
        </button>
      </div>

      <div className="flex flex-col md:flex-row gap-3 mb-4">
        <div className="flex-1 relative">
          <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
          <input
            type="text"
            placeholder="Rechercher un document..."
            value={search}
            onChange={(e) => { setSearch(e.target.value); setPage(1); }}
            className="w-full border rounded-lg p-3 pl-10"
          />
        </div>
        <select value={dossierFilter} onChange={(e) => { setDossierFilter(e.target.value); setPage(1); }} className="border rounded-lg p-3">
          {dossiersList.map((d) => <option key={d} value={d}>{d}</option>)}
        </select>
        <select value={typeFilter} onChange={(e) => { setTypeFilter(e.target.value); setPage(1); }} className="border rounded-lg p-3">
          <option value="Tous">Tous les types</option>
          <option value="PDF">PDF</option>
          <option value="DOCX">DOCX</option>
          <option value="XLSX">XLSX</option>
        </select>
        <select value={sortBy} onChange={(e) => setSortBy(e.target.value)} className="border rounded-lg p-3">
          <option value="date">Trier par date</option>
          <option value="nom">Trier par nom</option>
        </select>
      </div>

      {selected.length > 0 && (
        <div className="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4 flex justify-between items-center">
          <span className="text-sm text-blue-700">{selected.length} document(s) selectionne(s)</span>
          <button onClick={handleZipDownload} className="bg-blue-600 text-white px-3 py-1.5 rounded text-sm flex items-center gap-1">
            <Download className="w-4 h-4" />
            Telecharger en ZIP
          </button>
        </div>
      )}

      {filtered.length === 0 ? (
        <div className="bg-white border rounded-lg shadow p-12 text-center text-gray-500">
          <FolderOpen className="w-12 h-12 mx-auto mb-3 text-gray-300" />
          <p className="font-medium mb-1">Aucun document disponible.</p>
        </div>
      ) : (
        <>
          <div className="bg-white rounded-lg shadow overflow-hidden">
            <table className="w-full">
              <thead className="bg-gray-100">
                <tr>
                  <th className="p-3 w-10"></th>
                  <th className="p-3 text-left">Nom</th>
                  <th className="p-3 text-left">Type</th>
                  <th className="p-3 text-left">Dossier</th>
                  <th className="p-3 text-left">Auteur</th>
                  <th className="p-3 text-left">Date</th>
                  <th className="p-3 text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                {paginated.map((doc) => (
                  <tr key={doc.id} className="border-t hover:bg-gray-50">
                    <td className="p-3">
                      {tab === "documents" && (
                        <input type="checkbox" checked={selected.includes(doc.id)} onChange={() => toggleSelect(doc.id)} />
                      )}
                    </td>
                    <td className="p-3">
                      <button onClick={() => setDetailDoc(doc)} className="flex items-center gap-2 hover:underline text-left">
                        {getFileIcon(doc.type)}
                        {doc.nom}
                      </button>
                    </td>
                    <td className="p-3">
                      <span className={`px-2 py-1 rounded text-xs font-medium ${getBadgeClasses(doc.type)}`}>{doc.type}</span>
                    </td>
                    <td className="p-3 text-gray-500">{doc.dossier}</td>
                    <td className="p-3">{doc.auteur}</td>
                    <td className="p-3">{doc.date}</td>
                    <td className="p-3">
                      <div className="flex justify-center gap-2">
                        {tab === "documents" ? (
                          <>
                            <button title="Voir" onClick={() => setDetailDoc(doc)} className="p-2 rounded hover:bg-gray-100">
                              <Eye className="w-4 h-4 text-gray-600" />
                            </button>
                            <button title="Partager" onClick={() => setShareDoc(doc)} className="p-2 rounded hover:bg-gray-100">
                              <Share2 className="w-4 h-4 text-gray-600" />
                            </button>
                            <button title="Télécharger" className="p-2 rounded hover:bg-gray-100">
                              <Download className="w-4 h-4 text-gray-600" />
                            </button>
                            <button title="Modifier" onClick={() => openEdit(doc)} className="p-2 rounded hover:bg-gray-100">
                              <Pencil className="w-4 h-4 text-gray-600" />
                            </button>
                            <button title="Supprimer" onClick={() => setConfirmDeleteId(doc.id)} className="p-2 rounded hover:bg-red-50">
                              <Trash2 className="w-4 h-4 text-red-600" />
                            </button>
                          </>
                        ) : (
                          <button title="Restaurer" onClick={() => handleRestore(doc.id)} className="p-2 rounded hover:bg-green-50 flex items-center gap-1 text-green-700 text-xs">
                            <RotateCcw className="w-4 h-4" />
                            Restaurer
                          </button>
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          <div className="flex justify-center items-center gap-4 mt-6">
            <button onClick={() => setPage((p) => Math.max(1, p - 1))} disabled={page === 1} className="px-3 py-1 rounded border disabled:opacity-40">
              Précédent
            </button>
            <span className="text-sm text-gray-600">Page {page} / {totalPages}</span>
            <button onClick={() => setPage((p) => Math.min(totalPages, p + 1))} disabled={page === totalPages} className="px-3 py-1 rounded border disabled:opacity-40">
              Suivant
            </button>
          </div>
        </>
      )}

      {/* Modal Ajouter un document - FONCTIONNEL */}
      {showAddModal && (
        <div className="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
            <div className="flex justify-between items-center mb-4">
              <h2 className="text-lg font-semibold">Ajouter un document</h2>
              <button onClick={() => setShowAddModal(false)}><X className="w-5 h-5 text-gray-500" /></button>
            </div>
            <div className="space-y-3">
              <input
                type="text"
                placeholder="Nom du document"
                value={addForm.nom}
                onChange={(e) => setAddForm({ ...addForm, nom: e.target.value })}
                className="w-full border rounded-lg p-2"
              />
              <textarea
                placeholder="Description"
                value={addForm.description}
                onChange={(e) => setAddForm({ ...addForm, description: e.target.value })}
                className="w-full border rounded-lg p-2"
                rows={3}
              />
              <select
                value={addForm.type}
                onChange={(e) => setAddForm({ ...addForm, type: e.target.value })}
                className="w-full border rounded-lg p-2"
              >
                <option value="PDF">PDF</option>
                <option value="DOCX">DOCX</option>
                <option value="XLSX">XLSX</option>
              </select>
              <select
                value={addForm.dossier}
                onChange={(e) => setAddForm({ ...addForm, dossier: e.target.value })}
                className="w-full border rounded-lg p-2"
              >
                {dossiersList.filter((d) => d !== "Tous").map((d) => <option key={d}>{d}</option>)}
              </select>
              <input type="file" className="w-full text-sm" />
            </div>
            <div className="flex justify-end gap-2 mt-5">
              <button onClick={() => setShowAddModal(false)} className="px-4 py-2 rounded border">Annuler</button>
              <button onClick={handleAddDocument} className="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Ajouter</button>
            </div>
          </div>
        </div>
      )}

      {/* Modal Nouveau dossier - FONCTIONNEL */}
      {showFolderModal && (
        <div className="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg shadow-lg p-6 w-full max-w-sm">
            <div className="flex justify-between items-center mb-4">
              <h2 className="text-lg font-semibold">Nouveau dossier</h2>
              <button onClick={() => setShowFolderModal(false)}><X className="w-5 h-5 text-gray-500" /></button>
            </div>
            <input
              type="text"
              placeholder="Nom du dossier"
              value={newFolderName}
              onChange={(e) => setNewFolderName(e.target.value)}
              className="w-full border rounded-lg p-2 mb-4"
            />
            <div className="flex justify-end gap-2">
              <button onClick={() => setShowFolderModal(false)} className="px-4 py-2 rounded border">Annuler</button>
              <button onClick={handleAddFolder} className="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Créer</button>
            </div>
          </div>
        </div>
      )}

      {/* Modal Modifier */}
      {editDoc && (
        <div className="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
            <div className="flex justify-between items-center mb-4">
              <h2 className="text-lg font-semibold">Modifier le document</h2>
              <button onClick={() => setEditDoc(null)}><X className="w-5 h-5 text-gray-500" /></button>
            </div>
            <div className="space-y-3">
              <input
                type="text"
                value={editForm.nom}
                onChange={(e) => setEditForm({ ...editForm, nom: e.target.value })}
                className="w-full border rounded-lg p-2"
              />
              <textarea
                value={editForm.description}
                onChange={(e) => setEditForm({ ...editForm, description: e.target.value })}
                className="w-full border rounded-lg p-2"
                rows={3}
              />
              <select
                value={editForm.dossier}
                onChange={(e) => setEditForm({ ...editForm, dossier: e.target.value })}
                className="w-full border rounded-lg p-2"
              >
                {dossiersList.filter((d) => d !== "Tous").map((d) => <option key={d}>{d}</option>)}
              </select>
            </div>
            <div className="flex justify-end gap-2 mt-5">
              <button onClick={() => setEditDoc(null)} className="px-4 py-2 rounded border">Annuler</button>
              <button onClick={saveEdit} className="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Enregistrer</button>
            </div>
          </div>
        </div>
      )}

      {/* Modal Details + versions + AJOUT DE VERSION */}
      {detailDoc && (
        <div className="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
            <div className="flex justify-between items-center mb-4">
              <h2 className="text-lg font-semibold flex items-center gap-2">
                {getFileIcon(detailDoc.type)}
                {detailDoc.nom}
              </h2>
              <button onClick={() => setDetailDoc(null)}><X className="w-5 h-5 text-gray-500" /></button>
            </div>
            <p className="text-sm text-gray-600 mb-4">{detailDoc.description}</p>
            <div className="text-sm space-y-1 mb-4">
              <p><span className="text-gray-500">Dossier :</span> {detailDoc.dossier}</p>
              <p><span className="text-gray-500">Auteur :</span> {detailDoc.auteur}</p>
              <p><span className="text-gray-500">Taille :</span> {detailDoc.taille}</p>
              <p><span className="text-gray-500">Date :</span> {detailDoc.date}</p>
            </div>
            <div className="flex justify-between items-center mb-2">
              <h3 className="font-medium text-sm">Historique des versions</h3>
              <button onClick={handleAddVersion} className="text-xs text-blue-600 hover:underline">
                + Nouvelle version
              </button>
            </div>
            <ul className="space-y-1 text-sm">
              {detailDoc.versions.map((v) => (
                <li key={v.numero} className="flex justify-between border-b pb-1">
                  <span>{v.numero} — {v.auteur}</span>
                  <span className="text-gray-400">{v.date}</span>
                </li>
              ))}
            </ul>
          </div>
        </div>
      )}

      {shareDoc && (
        <div className="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
            <div className="flex justify-between items-center mb-4">
              <h2 className="text-lg font-semibold">Partager "{shareDoc.nom}"</h2>
              <button onClick={() => setShareDoc(null)}><X className="w-5 h-5 text-gray-500" /></button>
            </div>
            <input type="text" placeholder="Nom d'utilisateur ou service" className="w-full border rounded-lg p-2 mb-3" />
            <select className="w-full border rounded-lg p-2 mb-4">
              <option>Lecture seule</option>
              <option>Modification</option>
              <option>Suppression</option>
            </select>
            <div className="flex justify-end gap-2">
              <button onClick={() => setShareDoc(null)} className="px-4 py-2 rounded border">Annuler</button>
              <button onClick={() => setShareDoc(null)} className="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Partager</button>
            </div>
          </div>
        </div>
      )}

      {confirmDeleteId !== null && (
        <div className="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg shadow-lg p-6 w-full max-w-sm text-center">
            <p className="font-medium mb-4">Deplacer ce document vers la corbeille ?</p>
            <div className="flex justify-center gap-3">
              <button onClick={() => setConfirmDeleteId(null)} className="px-4 py-2 rounded border">Annuler</button>
              <button onClick={() => handleDelete(confirmDeleteId)} className="px-4 py-2 rounded bg-red-600 text-white hover:bg-red-700">Oui, supprimer</button>
            </div>
          </div>
        </div>
      )}
    </main>
  );
}