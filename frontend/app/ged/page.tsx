"use client";

import { useState, useEffect } from "react";
import { useSearchParams } from "next/navigation";
import {
  FileText, FileSpreadsheet, File, Eye, Download, Trash2, Pencil, Plus, Search,
  FolderOpen, X, Share2, RotateCcw, ChevronLeft, ChevronRight, Trash,
  AlertCircle, RefreshCw, LayoutGrid, Check, Copy, Mail, Users, Link as LinkIcon,
  CheckCircle, Clock, Archive
} from "lucide-react";

interface DocumentItem {
  id: number;
  nom: string;
  type: "PDF" | "DOCX" | "XLSX" | "PPTX";
  auteur: string;
  date: string;
  taille: string;
  dossier: string;
  description: string;
  supprime: boolean;
  versions: { numero: string; date: string; auteur: string }[];
  status?: "validé" | "en-attente" | "archivé";
}

const initialDocuments: DocumentItem[] = [
  { id: 1, nom: "Rapport_Projet.pdf", type: "PDF", auteur: "nouhaila", date: "08/07/2026", taille: "2.4 Mo", dossier: "Rapports", description: "Rapport final du projet", supprime: false, status: "validé", versions: [{ numero: "v1", date: "08/07/2026", auteur: "nouhaila" }] },
  { id: 2, nom: "Planning_Stage.docx", type: "DOCX", auteur: "kenza", date: "07/07/2026", taille: "856 Ko", dossier: "RH", description: "Planning des stages", supprime: false, status: "en-attente", versions: [{ numero: "v1", date: "07/07/2026", auteur: "kenza" }] },
  { id: 3, nom: "Budget.xlsx", type: "XLSX", auteur: "nizar", date: "05/07/2026", taille: "1.8 Mo", dossier: "Finance", description: "Budget prévisionnel", supprime: false, status: "validé", versions: [{ numero: "v1", date: "05/07/2026", auteur: "nizar" }] },
  { id: 4, nom: "Ancien_Rapport.pdf", type: "PDF", auteur: "nouhaila", date: "01/07/2026", taille: "1.2 Mo", dossier: "Rapports", description: "Ancien rapport", supprime: true, status: "archivé", versions: [{ numero: "v1", date: "01/07/2026", auteur: "nouhaila" }] },
];

const initialDossiers = ["Tous", "Rapports", "RH", "Finance", "Administratif", "Juridique", "Technique"];
const PAGE_SIZE = 5;

const fileIcon = (type: string) =>
  type === "PDF" ? <FileText className="w-4 h-4 text-[#E5392E]" /> :
  type === "XLSX" ? <FileSpreadsheet className="w-4 h-4 text-[#12A16A]" /> :
  type === "PPTX" ? <File className="w-4 h-4 text-[#E88A0C]" /> :
  <File className="w-4 h-4 text-[#1456F0]" />;

const badgeClasses = (type: string) =>
  type === "PDF" ? "bg-[#E5392E]/10 text-[#E5392E]" :
  type === "DOCX" ? "bg-[#1456F0]/10 text-[#1456F0]" :
  type === "XLSX" ? "bg-[#12A16A]/10 text-[#12A16A]" :
  "bg-[#7C3AED]/10 text-[#7C3AED]";

const todayFr = () => {
  const d = new Date();
  return `${String(d.getDate()).padStart(2, "0")}/${String(d.getMonth() + 1).padStart(2, "0")}/${d.getFullYear()}`;
};

function ConfirmModal({
  icon, title, message, docName, cancelLabel = "Annuler", confirmLabel, confirmColor, onCancel, onConfirm,
}: {
  icon: React.ReactNode; title: string; message: string; docName?: string;
  cancelLabel?: string; confirmLabel: string; confirmColor: string; onCancel: () => void; onConfirm: () => void;
}) {
  return (
    <div className="fixed inset-0 bg-[#0E1420]/30 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div className="bg-[#FFFFFF] rounded-xl shadow-2xl max-w-md w-full p-6 border border-[#D8DEE9]">
        <div className="flex items-start gap-3 mb-5">
          <div className="p-2 rounded-lg bg-[#F1F5F9] border border-[#D8DEE9]">{icon}</div>
          <div>
            <h3 className="text-[15px] font-semibold text-[#0E1420]">{title}</h3>
            <p className="text-[13px] text-[#5C6A82] mt-1">{message}</p>
            {docName && <p className="text-[13px] font-medium text-[#0E1420] mt-2">{docName}</p>}
          </div>
        </div>
        <div className="flex gap-2">
          <button onClick={onCancel} className="flex-1 px-4 py-2 rounded-lg border border-[#D8DEE9] text-[#2B3242] hover:bg-[#F1F5F9] transition-colors text-[13px] font-medium">
            {cancelLabel}
          </button>
          <button onClick={onConfirm} className={`flex-1 px-4 py-2 rounded-lg text-white text-[13px] font-medium transition-colors ${confirmColor}`}>
            {confirmLabel}
          </button>
        </div>
      </div>
    </div>
  );
}

export default function GedPage() {
  const [documents, setDocuments] = useState<DocumentItem[]>(initialDocuments);
  const [dossiersList] = useState<string[]>(initialDossiers);
  const [search, setSearch] = useState("");
  const [typeFilter, setTypeFilter] = useState("Tous");
  const [dossierFilter, setDossierFilter] = useState("Tous");
  const [sortBy, setSortBy] = useState("date");
  const [page, setPage] = useState(1);
  const [selected, setSelected] = useState<number[]>([]);
  const [showAddModal, setShowAddModal] = useState(false);
  const [addForm, setAddForm] = useState({ nom: "", description: "", type: "PDF", dossier: "Rapports" });
  const [showCorbeille, setShowCorbeille] = useState(false);
  const [confirmRestoreId, setConfirmRestoreId] = useState<number | null>(null);
  const [confirmDeletePermanentId, setConfirmDeletePermanentId] = useState<number | null>(null);
  
  // États pour les modales
  const [detailDoc, setDetailDoc] = useState<DocumentItem | null>(null);
  const [shareDoc, setShareDoc] = useState<DocumentItem | null>(null);
  const [editDoc, setEditDoc] = useState<DocumentItem | null>(null);
  const [editForm, setEditForm] = useState({ nom: "", description: "", dossier: "" });
  
  // États pour les notifications
  const [toast, setToast] = useState<{ message: string; type: string } | null>(null);
  
  // États pour le partage
  const [shareEmail, setShareEmail] = useState("");
  const [sharePermission, setSharePermission] = useState("read");
  const [copiedLink, setCopiedLink] = useState(false);
  
  // États pour le téléchargement
  const [isDownloading, setIsDownloading] = useState(false);
  const [downloadProgress, setDownloadProgress] = useState(0);

  const searchParams = useSearchParams();
  useEffect(() => {
    if (searchParams.get("action") === "upload") setShowAddModal(true);
  }, [searchParams]);

  const filtered = (showCorbeille ? documents.filter((d) => d.supprime) : documents.filter((d) => !d.supprime))
    .filter((d) => d.nom.toLowerCase().includes(search.toLowerCase()))
    .filter((d) => typeFilter === "Tous" || d.type === typeFilter)
    .filter((d) => dossierFilter === "Tous" || d.dossier === dossierFilter)
    .sort((a, b) => sortBy === "nom" ? a.nom.localeCompare(b.nom) : sortBy === "auteur" ? a.auteur.localeCompare(b.auteur) : b.date.localeCompare(a.date));

  const totalPages = Math.max(1, Math.ceil(filtered.length / PAGE_SIZE));
  const paginated = filtered.slice((page - 1) * PAGE_SIZE, page * PAGE_SIZE);
  const corbeilleCount = documents.filter((d) => d.supprime).length;

  const active = documents.filter((d) => !d.supprime);
  const total = Math.max(1, active.length);
  const typeLegend = [
    { label: "PDF", value: active.filter((d) => d.type === "PDF").length, color: "#E5392E" },
    { label: "DOCX", value: active.filter((d) => d.type === "DOCX").length, color: "#1456F0" },
    { label: "XLSX", value: active.filter((d) => d.type === "XLSX").length, color: "#12A16A" },
    { label: "Autres", value: active.filter((d) => !["PDF", "DOCX", "XLSX"].includes(d.type)).length, color: "#7A869C" },
  ];
  let acc = 0;
  const pieGradient = typeLegend.map((t) => {
    const start = (acc / total) * 360;
    acc += t.value;
    return `${t.color} ${start}deg ${(acc / total) * 360}deg`;
  }).join(", ");

  // ========== FONCTIONS DES BOUTONS ==========

  // Téléchargement
  const handleDownload = (doc: DocumentItem) => {
    setIsDownloading(true);
    setDownloadProgress(0);
    const interval = setInterval(() => {
      setDownloadProgress(prev => {
        if (prev >= 100) {
          clearInterval(interval);
          setIsDownloading(false);
          setToast({ message: `✅ "${doc.nom}" téléchargé avec succès !`, type: "success" });
          setTimeout(() => setToast(null), 3000);
          return 100;
        }
        return prev + 10;
      });
    }, 200);
  };

  // Partager
  const handleShare = (doc: DocumentItem) => {
    setShareDoc(doc);
    setShareEmail("");
    setSharePermission("read");
  };

  const handleSendShare = () => {
    if (!shareEmail.trim()) {
      setToast({ message: "⚠️ Veuillez entrer une adresse email", type: "error" });
      setTimeout(() => setToast(null), 3000);
      return;
    }
    setToast({ message: `📧 Document partagé avec ${shareEmail} (${sharePermission === "read" ? "Lecture" : sharePermission === "edit" ? "Modification" : "Administration"})`, type: "success" });
    setShareDoc(null);
    setTimeout(() => setToast(null), 3000);
  };

  const handleCopyLink = () => {
    navigator.clipboard.writeText(`https://plateforme-2m.com/document/${shareDoc?.id}`);
    setCopiedLink(true);
    setTimeout(() => setCopiedLink(false), 2000);
  };

  // Modifier
  const openEdit = (doc: DocumentItem) => {
    setEditDoc(doc);
    setEditForm({ nom: doc.nom, description: doc.description, dossier: doc.dossier });
  };

  const saveEdit = () => {
    if (!editDoc) return;
    setDocuments((docs) =>
      docs.map((d) =>
        d.id === editDoc.id
          ? { ...d, nom: editForm.nom, description: editForm.description, dossier: editForm.dossier }
          : d
      )
    );
    setEditDoc(null);
    setToast({ message: `✅ "${editForm.nom}" modifié avec succès !`, type: "success" });
    setTimeout(() => setToast(null), 3000);
  };

  // Voir détails
  const handleView = (doc: DocumentItem) => {
    setDetailDoc(doc);
  };

  // Supprimer
  const handleDelete = (id: number) => {
    setDocuments((docs) => docs.map((d) => (d.id === id ? { ...d, supprime: true } : d)));
    setToast({ message: `🗑️ Document déplacé vers la corbeille`, type: "success" });
    setTimeout(() => setToast(null), 3000);
  };

  // Restaurer
  const handleRestore = (id: number) => {
    setDocuments((docs) => docs.map((d) => (d.id === id ? { ...d, supprime: false } : d)));
    setConfirmRestoreId(null);
    setToast({ message: `✅ Document restauré avec succès !`, type: "success" });
    setTimeout(() => setToast(null), 3000);
    if (documents.filter((d) => d.id !== id && d.supprime).length === 0) setShowCorbeille(false);
  };

  const handleDeletePermanent = (id: number) => {
    setDocuments((docs) => docs.filter((d) => d.id !== id));
    setConfirmDeletePermanentId(null);
    setToast({ message: `🗑️ Document supprimé définitivement`, type: "success" });
    setTimeout(() => setToast(null), 3000);
    if (documents.filter((d) => d.id !== id && d.supprime).length === 0) setShowCorbeille(false);
  };

  const handleEmptyTrash = () => {
    if (window.confirm("Voulez-vous vraiment vider la corbeille ? Cette action est irréversible.")) {
      setDocuments((docs) => docs.filter((d) => !d.supprime));
      setShowCorbeille(false);
      setToast({ message: `🗑️ Corbeille vidée avec succès`, type: "success" });
      setTimeout(() => setToast(null), 3000);
    }
  };

  const handleAddDocument = () => {
    if (!addForm.nom.trim()) {
      setToast({ message: "⚠️ Le nom du document est obligatoire", type: "error" });
      setTimeout(() => setToast(null), 3000);
      return;
    }
    const newDoc: DocumentItem = {
      id: Math.max(0, ...documents.map((d) => d.id)) + 1,
      nom: addForm.nom,
      type: addForm.type as DocumentItem["type"],
      auteur: "nouhaila",
      date: todayFr(),
      taille: "0 KB",
      dossier: addForm.dossier,
      description: addForm.description,
      supprime: false,
      status: "en-attente",
      versions: [{ numero: "v1", date: todayFr(), auteur: "nouhaila" }],
    };
    setDocuments((docs) => [newDoc, ...docs]);
    setAddForm({ nom: "", description: "", type: "PDF", dossier: "Rapports" });
    setShowAddModal(false);
    setPage(1);
    setToast({ message: `✅ "${newDoc.nom}" ajouté avec succès !`, type: "success" });
    setTimeout(() => setToast(null), 3000);
  };

  const toggleSelect = (id: number) => setSelected((s) => (s.includes(id) ? s.filter((x) => x !== id) : [...s, id]));
  
  const handleZipDownload = () => {
    if (selected.length === 0) {
      setToast({ message: "⚠️ Sélectionnez au moins un document", type: "error" });
      setTimeout(() => setToast(null), 3000);
      return;
    }
    setToast({ message: `📦 Téléchargement de ${selected.length} document(s) en ZIP...`, type: "success" });
    setSelected([]);
    setTimeout(() => setToast(null), 3000);
  };

  const toggleCorbeille = () => { setShowCorbeille(!showCorbeille); setPage(1); setSearch(""); setSelected([]); };

  return (
    <main className="p-6 bg-[#F7F9FC] min-h-screen font-sans text-[#0E1420]">
      {/* Toast Notification */}
      {toast && (
        <div className="fixed bottom-6 right-6 z-50 animate-in slide-in-from-bottom-5 duration-300">
          <div className={`flex items-center gap-3 px-5 py-3 rounded-xl shadow-2xl border ${
            toast.type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' :
            toast.type === 'error' ? 'bg-red-50 border-red-200 text-red-800' :
            'bg-blue-50 border-blue-200 text-blue-800'
          }`}>
            {toast.type === 'success' && <CheckCircle className="w-5 h-5 text-emerald-500" />}
            {toast.type === 'error' && <AlertCircle className="w-5 h-5 text-red-500" />}
            <span className="font-medium text-[14px]">{toast.message}</span>
          </div>
        </div>
      )}

      <div className="flex items-center justify-between mb-6">
        <div>
          <p className="text-[11px] font-semibold tracking-[0.14em] text-[#1456F0] uppercase mb-1.5">GED</p>
          <h1 className="text-[20px] font-semibold tracking-tight">Gestion électronique des documents</h1>
          <p className="text-[#5C6A82] text-[13px] mt-0.5">
            {filtered.length} document{filtered.length > 1 ? "s" : ""} disponible{filtered.length > 1 ? "s" : ""}
            {showCorbeille && " dans la corbeille"}
          </p>
        </div>
        <div className="flex items-center gap-2">
          <button
            onClick={toggleCorbeille}
            className={`px-3 py-1.5 rounded-lg flex items-center gap-2 text-[13px] font-medium transition-colors ${
              showCorbeille ? "bg-[#1456F0] text-white" : "bg-[#FFFFFF] border border-[#D8DEE9] text-[#2B3242] hover:border-[#C7D0DE]"
            }`}
          >
            <Trash className="w-3.5 h-3.5" />
            Corbeille
            {corbeilleCount > 0 && (
              <span className={`px-1.5 py-0.5 rounded text-[11px] ${showCorbeille ? "bg-white/20" : "bg-[#E5392E]/10 text-[#E5392E]"}`}>
                {corbeilleCount}
              </span>
            )}
          </button>
          {showCorbeille && corbeilleCount > 0 && (
            <button onClick={handleEmptyTrash} className="px-3 py-1.5 rounded-lg bg-[#E5392E] text-white flex items-center gap-2 hover:bg-[#E5392E]/90 transition-colors text-[13px] font-medium">
              <Trash2 className="w-3.5 h-3.5" /> Vider la corbeille
            </button>
          )}
          {showCorbeille && (
            <button onClick={toggleCorbeille} className="px-3 py-1.5 rounded-lg bg-[#F1F5F9] border border-[#D8DEE9] text-[#2B3242] flex items-center gap-2 hover:border-[#C7D0DE] transition-colors text-[13px] font-medium">
              <RefreshCw className="w-3.5 h-3.5" /> Retour
            </button>
          )}
          {!showCorbeille && (
            <button onClick={() => setShowAddModal(true)} className="bg-[#1456F0] text-white px-3 py-1.5 rounded-lg flex items-center gap-2 hover:bg-[#0E3FC4] transition-colors text-[13px] font-medium">
              <Plus className="w-3.5 h-3.5" /> Ajouter un document
            </button>
          )}
        </div>
      </div>

      <div className="bg-[#FFFFFF] rounded-xl border border-[#E3E8F0] p-4 mb-4">
        <div className="flex flex-col md:flex-row gap-2">
          <div className="flex-1 relative">
            <Search className="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-[#7A869C]" />
            <input
              type="text"
              placeholder={showCorbeille ? "Rechercher dans la corbeille..." : "Rechercher un document..."}
              value={search}
              onChange={(e) => { setSearch(e.target.value); setPage(1); }}
              className="w-full bg-[#F7F9FC] border border-[#E3E8F0] rounded-lg py-2 pl-9 pr-3 text-[13px] focus:outline-none focus:border-[#1456F0] transition-colors placeholder:text-[#94A0B4]"
            />
          </div>
          <div className="flex gap-2 flex-wrap">
            <select value={dossierFilter} onChange={(e) => { setDossierFilter(e.target.value); setPage(1); }} className="bg-[#F7F9FC] border border-[#E3E8F0] rounded-lg py-2 px-3 text-[13px] focus:outline-none focus:border-[#1456F0] transition-colors">
              {dossiersList.map((d) => <option key={d} value={d}>{d}</option>)}
            </select>
            <select value={typeFilter} onChange={(e) => { setTypeFilter(e.target.value); setPage(1); }} className="bg-[#F7F9FC] border border-[#E3E8F0] rounded-lg py-2 px-3 text-[13px] focus:outline-none focus:border-[#1456F0] transition-colors">
              <option value="Tous">Tous les types</option>
              <option value="PDF">PDF</option>
              <option value="DOCX">DOCX</option>
              <option value="XLSX">XLSX</option>
              <option value="PPTX">PPTX</option>
            </select>
            <select value={sortBy} onChange={(e) => setSortBy(e.target.value)} className="bg-[#F7F9FC] border border-[#E3E8F0] rounded-lg py-2 px-3 text-[13px] focus:outline-none focus:border-[#1456F0] transition-colors">
              <option value="date">Trier par date</option>
              <option value="nom">Trier par nom</option>
              <option value="auteur">Trier par auteur</option>
            </select>
          </div>
        </div>
      </div>

      {!showCorbeille && selected.length > 0 && (
        <div className="bg-[#1456F0]/10 border border-[#1456F0]/20 rounded-lg p-3 mb-4 flex justify-between items-center">
          <span className="text-[13px] text-[#1456F0]">{selected.length} document(s) sélectionné(s)</span>
          <button onClick={handleZipDownload} className="bg-[#1456F0] text-white px-3 py-1.5 rounded-lg text-[12px] flex items-center gap-1 hover:bg-[#0E3FC4] transition-colors font-medium">
            <Download className="w-3.5 h-3.5" /> Télécharger en ZIP
          </button>
        </div>
      )}

      {filtered.length === 0 ? (
        <div className="bg-[#FFFFFF] border border-[#E3E8F0] rounded-xl p-12 text-center">
          <FolderOpen className="w-8 h-8 mx-auto mb-3 text-[#C7D0DE]" />
          <p className="font-medium text-[14px] mb-1">{showCorbeille ? "La corbeille est vide" : "Aucun document disponible"}</p>
          <p className="text-[13px] text-[#7A869C]">{showCorbeille ? "Les documents supprimés apparaîtront ici" : "Commencez par ajouter un document"}</p>
        </div>
      ) : (
        <div className="relative bg-[#FFFFFF] border border-[#E3E8F0] rounded-xl overflow-hidden">
          <span className="absolute top-0 left-0 right-0 h-[2px] bg-[#1456F0]" />
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-[#F1F5F9] border-b border-[#E3E8F0]">
                <tr>
                  {!showCorbeille && (
                    <th className="p-3 w-10">
                      <input
                        type="checkbox"
                        checked={selected.length === paginated.length && paginated.length > 0}
                        onChange={() => setSelected(selected.length === paginated.length ? [] : paginated.map((d) => d.id))}
                        className="rounded border-[#D8DEE9] bg-[#F7F9FC] accent-[#1456F0]"
                      />
                    </th>
                  )}
                  <th className="p-3 text-left text-[#5C6A82] font-medium text-[12px]">Nom</th>
                  <th className="p-3 text-left text-[#5C6A82] font-medium text-[12px]">Type</th>
                  <th className="p-3 text-left text-[#5C6A82] font-medium text-[12px]">Dossier</th>
                  <th className="p-3 text-left text-[#5C6A82] font-medium text-[12px]">Auteur</th>
                  <th className="p-3 text-left text-[#5C6A82] font-medium text-[12px]">Date</th>
                  <th className="p-3 text-center text-[#5C6A82] font-medium text-[12px]">Actions</th>
                </tr>
              </thead>
              <tbody>
                {paginated.map((doc) => (
                  <tr key={doc.id} className="border-t border-[#EAEEF3] hover:bg-[#F4F7FB] transition-colors">
                    {!showCorbeille && (
                      <td className="p-3">
                        <input type="checkbox" checked={selected.includes(doc.id)} onChange={() => toggleSelect(doc.id)} className="rounded border-[#D8DEE9] bg-[#F7F9FC] accent-[#1456F0]" />
                      </td>
                    )}
                    <td className="p-3">
                      <div className="flex items-center gap-2.5">
                        {fileIcon(doc.type)}
                        <span className={`text-[13px] font-medium ${doc.supprime ? "text-[#7A869C] line-through" : "text-[#0E1420]"}`}>{doc.nom}</span>
                      </div>
                    </td>
                    <td className="p-3"><span className={`px-2 py-0.5 rounded text-[11px] font-medium ${badgeClasses(doc.type)}`}>{doc.type}</span></td>
                    <td className="p-3 text-[#5C6A82] text-[13px]">{doc.dossier}</td>
                    <td className="p-3 text-[#5C6A82] text-[13px]">{doc.auteur}</td>
                    <td className="p-3 text-[#5C6A82] font-mono text-[12px]">{doc.date}</td>
                    <td className="p-3">
                      <div className="flex justify-center gap-0.5">
                        {showCorbeille ? (
                          <>
                            <button title="Restaurer" onClick={() => setConfirmRestoreId(doc.id)} className="p-1.5 rounded-md hover:bg-[#E3E8F0] transition-colors">
                              <RotateCcw className="w-3.5 h-3.5 text-[#12A16A]" />
                            </button>
                            <button title="Supprimer définitivement" onClick={() => setConfirmDeletePermanentId(doc.id)} className="p-1.5 rounded-md hover:bg-[#E3E8F0] transition-colors">
                              <Trash2 className="w-3.5 h-3.5 text-[#E5392E]" />
                            </button>
                          </>
                        ) : (
                          <>
                            <button title="Voir" onClick={() => handleView(doc)} className="p-1.5 rounded-md hover:bg-[#E3E8F0] transition-colors">
                              <Eye className="w-3.5 h-3.5 text-[#5C6A82]" />
                            </button>
                            <button title="Partager" onClick={() => handleShare(doc)} className="p-1.5 rounded-md hover:bg-[#E3E8F0] transition-colors">
                              <Share2 className="w-3.5 h-3.5 text-[#5C6A82]" />
                            </button>
                            <button title="Télécharger" onClick={() => handleDownload(doc)} disabled={isDownloading} className="p-1.5 rounded-md hover:bg-[#E3E8F0] transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                              {isDownloading ? (
                                <div className="w-3.5 h-3.5 border-2 border-[#1456F0] border-t-transparent rounded-full animate-spin" />
                              ) : (
                                <Download className="w-3.5 h-3.5 text-[#5C6A82]" />
                              )}
                            </button>
                            <button title="Modifier" onClick={() => openEdit(doc)} className="p-1.5 rounded-md hover:bg-[#E3E8F0] transition-colors">
                              <Pencil className="w-3.5 h-3.5 text-[#5C6A82]" />
                            </button>
                            <button title="Supprimer" onClick={() => handleDelete(doc.id)} className="p-1.5 rounded-md hover:bg-[#E3E8F0] transition-colors">
                              <Trash2 className="w-3.5 h-3.5 text-[#E5392E]" />
                            </button>
                          </>
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {filtered.length > 0 && (
        <div className="flex justify-between items-center mt-5">
          <div className="text-[12px] text-[#7A869C]">
            Affichage de {(page - 1) * PAGE_SIZE + 1} à {Math.min(page * PAGE_SIZE, filtered.length)} sur {filtered.length} documents
          </div>
          <div className="flex items-center gap-2">
            <button onClick={() => setPage((p) => Math.max(1, p - 1))} disabled={page === 1} className="px-3 py-1.5 rounded-lg border border-[#E3E8F0] disabled:opacity-30 disabled:cursor-not-allowed hover:border-[#C7D0DE] transition-colors flex items-center gap-1 text-[12px] text-[#2B3242]">
              <ChevronLeft className="w-3.5 h-3.5" /> Précédent
            </button>
            <span className="text-[12px] text-[#7A869C]">Page {page} / {totalPages}</span>
            <button onClick={() => setPage((p) => Math.min(totalPages, p + 1))} disabled={page === totalPages} className="px-3 py-1.5 rounded-lg border border-[#E3E8F0] disabled:opacity-30 disabled:cursor-not-allowed hover:border-[#C7D0DE] transition-colors flex items-center gap-1 text-[12px] text-[#2B3242]">
              Suivant <ChevronRight className="w-3.5 h-3.5" />
            </button>
          </div>
        </div>
      )}

      {!showCorbeille && (
        <div className="bg-[#FFFFFF] border border-[#E3E8F0] rounded-xl p-5 mt-6 max-w-sm">
          <h2 className="font-medium text-[13px] flex items-center gap-2 mb-4">
            <LayoutGrid className="w-3.5 h-3.5 text-[#5C6A82]" /> Documents par type
          </h2>
          <div className="flex items-center gap-6">
            <div className="w-24 h-24 rounded-full shrink-0" style={{ background: `conic-gradient(${pieGradient})` }} />
            <div className="space-y-2 text-[13px] flex-1">
              {typeLegend.map((t) => (
                <div key={t.label} className="flex items-center justify-between">
                  <span className="flex items-center gap-2">
                    <span className="w-2 h-2 rounded-full" style={{ backgroundColor: t.color }} />
                    <span className="text-[#5C6A82]">{t.label}</span>
                  </span>
                  <span className="font-medium text-[#0E1420]">{Math.round((t.value / total) * 100)}%</span>
                </div>
              ))}
            </div>
          </div>
        </div>
      )}

      {/* Modal Détails */}
      {detailDoc && (
        <div className="fixed inset-0 bg-[#0E1420]/30 backdrop-blur-sm flex items-center justify-center z-50 p-4">
          <div className="bg-[#FFFFFF] rounded-xl shadow-2xl max-w-md w-full p-6 border border-[#D8DEE9]">
            <div className="flex justify-between items-center mb-4">
              <h3 className="text-[15px] font-semibold flex items-center gap-2">
                {fileIcon(detailDoc.type)} {detailDoc.nom}
              </h3>
              <button onClick={() => setDetailDoc(null)} className="p-1.5 rounded-md hover:bg-[#E3E8F0] transition-colors">
                <X className="w-4 h-4 text-[#5C6A82]" />
              </button>
            </div>
            <div className="space-y-3">
              <p className="text-[13px] text-[#5C6A82]">{detailDoc.description}</p>
              <div className="flex items-center gap-2 text-[13px]"><span className="text-[#5C6A82]">Dossier :</span><span className="font-medium">{detailDoc.dossier}</span></div>
              <div className="flex items-center gap-2 text-[13px]"><span className="text-[#5C6A82]">Auteur :</span><span className="font-medium">{detailDoc.auteur}</span></div>
              <div className="flex items-center gap-2 text-[13px]"><span className="text-[#5C6A82]">Taille :</span><span className="font-medium">{detailDoc.taille}</span></div>
              <div className="flex items-center gap-2 text-[13px]"><span className="text-[#5C6A82]">Date :</span><span className="font-medium">{detailDoc.date}</span></div>
              <div>
                <span className={`px-2.5 py-1 rounded-full text-[11px] font-medium border ${detailDoc.status === "validé" ? "bg-emerald-100 text-emerald-700 border-emerald-200" : detailDoc.status === "en-attente" ? "bg-amber-100 text-amber-700 border-amber-200" : "bg-gray-100 text-gray-700 border-gray-200"}`}>
                  {detailDoc.status === "validé" ? "✅ Validé" : detailDoc.status === "en-attente" ? "⏳ En attente" : "📦 Archivé"}
                </span>
              </div>
            </div>
            <div className="flex gap-2 mt-5">
              <button onClick={() => setDetailDoc(null)} className="flex-1 px-4 py-2 rounded-lg border border-[#D8DEE9] text-[#2B3242] hover:bg-[#F1F5F9] transition-colors text-[13px] font-medium">Fermer</button>
              <button onClick={() => handleDownload(detailDoc)} disabled={isDownloading} className="flex-1 px-4 py-2 rounded-lg bg-[#1456F0] text-white text-[13px] font-medium hover:bg-[#0E3FC4] transition-colors flex items-center justify-center gap-2">
                {isDownloading ? <><div className="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin" /> {downloadProgress}%</> : <><Download className="w-3.5 h-3.5" /> Télécharger</>}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Modal Partager */}
      {shareDoc && (
        <div className="fixed inset-0 bg-[#0E1420]/30 backdrop-blur-sm flex items-center justify-center z-50 p-4">
          <div className="bg-[#FFFFFF] rounded-xl shadow-2xl max-w-md w-full p-6 border border-[#D8DEE9]">
            <div className="flex justify-between items-center mb-4">
              <h3 className="text-[15px] font-semibold flex items-center gap-2"><Share2 className="w-4 h-4 text-[#1456F0]" /> Partager "{shareDoc.nom}"</h3>
              <button onClick={() => setShareDoc(null)} className="p-1.5 rounded-md hover:bg-[#E3E8F0] transition-colors"><X className="w-4 h-4 text-[#5C6A82]" /></button>
            </div>
            <div className="space-y-4">
              <div>
                <label className="text-[12px] font-medium text-[#2B3242] block mb-1.5"><Mail className="w-3.5 h-3.5 inline mr-1.5 text-[#5C6A82]" /> Adresse email</label>
                <input type="email" placeholder="exemple@domaine.com" value={shareEmail} onChange={(e) => setShareEmail(e.target.value)} className="w-full px-3 py-2 rounded-lg border border-[#E3E8F0] bg-[#F7F9FC] text-[13px] focus:outline-none focus:border-[#1456F0] transition-colors" />
              </div>
              <div>
                <label className="text-[12px] font-medium text-[#2B3242] block mb-1.5"><Users className="w-3.5 h-3.5 inline mr-1.5 text-[#5C6A82]" /> Permissions</label>
                <select value={sharePermission} onChange={(e) => setSharePermission(e.target.value)} className="w-full px-3 py-2 rounded-lg border border-[#E3E8F0] bg-[#F7F9FC] text-[13px] focus:outline-none focus:border-[#1456F0] transition-colors">
                  <option value="read">Lecture seule</option>
                  <option value="edit">Modification</option>
                  <option value="admin">Administration</option>
                </select>
              </div>
              <div>
                <label className="text-[12px] font-medium text-[#2B3242] block mb-1.5"><LinkIcon className="w-3.5 h-3.5 inline mr-1.5 text-[#5C6A82]" /> Lien de partage</label>
                <div className="flex gap-2">
                  <input type="text" value={`https://plateforme-2m.com/document/${shareDoc.id}`} readOnly className="flex-1 px-3 py-2 rounded-lg border border-[#E3E8F0] bg-[#F7F9FC] text-[12px] text-[#5C6A82] cursor-default" />
                  <button onClick={handleCopyLink} className="px-3 py-2 rounded-lg bg-[#1456F0] text-white hover:bg-[#0E3FC4] transition-colors">{copiedLink ? <Check className="w-3.5 h-3.5" /> : <Copy className="w-3.5 h-3.5" />}</button>
                </div>
                {copiedLink && <p className="text-[11px] text-emerald-600 mt-1">✅ Lien copié !</p>}
              </div>
              <div className="flex gap-2 pt-2">
                <button onClick={() => setShareDoc(null)} className="flex-1 px-4 py-2 rounded-lg border border-[#D8DEE9] text-[#2B3242] hover:bg-[#F1F5F9] transition-colors text-[13px] font-medium">Annuler</button>
                <button onClick={handleSendShare} className="flex-1 px-4 py-2 rounded-lg bg-[#1456F0] text-white text-[13px] font-medium hover:bg-[#0E3FC4] transition-colors flex items-center justify-center gap-2"><Share2 className="w-3.5 h-3.5" /> Partager</button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Modal Modifier */}
      {editDoc && (
        <div className="fixed inset-0 bg-[#0E1420]/30 backdrop-blur-sm flex items-center justify-center z-50 p-4">
          <div className="bg-[#FFFFFF] rounded-xl shadow-2xl max-w-md w-full p-6 border border-[#D8DEE9]">
            <div className="flex justify-between items-center mb-4">
              <h3 className="text-[15px] font-semibold flex items-center gap-2"><Pencil className="w-4 h-4 text-[#1456F0]" /> Modifier le document</h3>
              <button onClick={() => setEditDoc(null)} className="p-1.5 rounded-md hover:bg-[#E3E8F0] transition-colors"><X className="w-4 h-4 text-[#5C6A82]" /></button>
            </div>
            <div className="space-y-4">
              <div>
                <label className="text-[12px] font-medium text-[#2B3242] block mb-1.5">Nom du document</label>
                <input type="text" value={editForm.nom} onChange={(e) => setEditForm({ ...editForm, nom: e.target.value })} className="w-full px-3 py-2 rounded-lg border border-[#E3E8F0] bg-[#F7F9FC] text-[13px] focus:outline-none focus:border-[#1456F0] transition-colors" />
              </div>
              <div>
                <label className="text-[12px] font-medium text-[#2B3242] block mb-1.5">Description</label>
                <textarea value={editForm.description} onChange={(e) => setEditForm({ ...editForm, description: e.target.value })} rows={3} className="w-full px-3 py-2 rounded-lg border border-[#E3E8F0] bg-[#F7F9FC] text-[13px] focus:outline-none focus:border-[#1456F0] transition-colors resize-none" />
              </div>
              <div>
                <label className="text-[12px] font-medium text-[#2B3242] block mb-1.5">Dossier</label>
                <select value={editForm.dossier} onChange={(e) => setEditForm({ ...editForm, dossier: e.target.value })} className="w-full px-3 py-2 rounded-lg border border-[#E3E8F0] bg-[#F7F9FC] text-[13px] focus:outline-none focus:border-[#1456F0] transition-colors">
                  {dossiersList.filter((d) => d !== "Tous").map((d) => <option key={d} value={d}>{d}</option>)}
                </select>
              </div>
              <div className="flex gap-2 pt-2">
                <button onClick={() => setEditDoc(null)} className="flex-1 px-4 py-2 rounded-lg border border-[#D8DEE9] text-[#2B3242] hover:bg-[#F1F5F9] transition-colors text-[13px] font-medium">Annuler</button>
                <button onClick={saveEdit} className="flex-1 px-4 py-2 rounded-lg bg-[#1456F0] text-white text-[13px] font-medium hover:bg-[#0E3FC4] transition-colors">Enregistrer</button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Modales de confirmation */}
      {confirmRestoreId !== null && (
        <ConfirmModal
          icon={<RotateCcw className="w-4 h-4 text-[#12A16A]" />}
          title="Restaurer le document"
          message="Voulez-vous restaurer ce document depuis la corbeille ?"
          docName={documents.find((d) => d.id === confirmRestoreId)?.nom}
          confirmLabel="Restaurer"
          confirmColor="bg-[#12A16A] hover:bg-[#12A16A]/90"
          onCancel={() => setConfirmRestoreId(null)}
          onConfirm={() => handleRestore(confirmRestoreId)}
        />
      )}
      
      {confirmDeletePermanentId !== null && (
        <ConfirmModal
          icon={<AlertCircle className="w-4 h-4 text-[#E5392E]" />}
          title="Supprimer définitivement"
          message="Cette action est irréversible. Voulez-vous vraiment supprimer ce document ?"
          docName={documents.find((d) => d.id === confirmDeletePermanentId)?.nom}
          confirmLabel="Supprimer"
          confirmColor="bg-[#E5392E] hover:bg-[#E5392E]/90"
          onCancel={() => setConfirmDeletePermanentId(null)}
          onConfirm={() => handleDeletePermanent(confirmDeletePermanentId)}
        />
      )}

      {/* Modal Ajouter un document */}
      {showAddModal && (
        <div className="fixed inset-0 bg-[#0E1420]/30 backdrop-blur-sm flex items-center justify-center z-50 p-4">
          <div className="bg-[#FFFFFF] rounded-xl shadow-2xl max-w-md w-full p-6 border border-[#D8DEE9]">
            <div className="flex justify-between items-center mb-5">
              <h3 className="text-[15px] font-semibold flex items-center gap-2"><Plus className="w-4 h-4 text-[#1456F0]" /> Ajouter un document</h3>
              <button onClick={() => setShowAddModal(false)} className="p-1.5 rounded-md hover:bg-[#E3E8F0] transition-colors"><X className="w-4 h-4 text-[#5C6A82]" /></button>
            </div>
            <div className="space-y-4">
              <div>
                <label className="text-[12px] font-medium text-[#2B3242] block mb-1.5">Nom du document</label>
                <input type="text" placeholder="Ex: Rapport_Projet.pdf" value={addForm.nom} onChange={(e) => setAddForm({ ...addForm, nom: e.target.value })} className="w-full px-3 py-2 rounded-lg border border-[#E3E8F0] bg-[#F7F9FC] text-[13px] focus:outline-none focus:border-[#1456F0] transition-colors placeholder:text-[#94A0B4]" />
              </div>
              <div>
                <label className="text-[12px] font-medium text-[#2B3242] block mb-1.5">Description</label>
                <textarea placeholder="Description du document" value={addForm.description} onChange={(e) => setAddForm({ ...addForm, description: e.target.value })} rows={3} className="w-full px-3 py-2 rounded-lg border border-[#E3E8F0] bg-[#F7F9FC] text-[13px] focus:outline-none focus:border-[#1456F0] transition-colors resize-none placeholder:text-[#94A0B4]" />
              </div>
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="text-[12px] font-medium text-[#2B3242] block mb-1.5">Type</label>
                  <select value={addForm.type} onChange={(e) => setAddForm({ ...addForm, type: e.target.value })} className="w-full px-3 py-2 rounded-lg border border-[#E3E8F0] bg-[#F7F9FC] text-[13px] focus:outline-none focus:border-[#1456F0] transition-colors">
                    <option value="PDF">PDF</option>
                    <option value="DOCX">DOCX</option>
                    <option value="XLSX">XLSX</option>
                    <option value="PPTX">PPTX</option>
                  </select>
                </div>
                <div>
                  <label className="text-[12px] font-medium text-[#2B3242] block mb-1.5">Dossier</label>
                  <select value={addForm.dossier} onChange={(e) => setAddForm({ ...addForm, dossier: e.target.value })} className="w-full px-3 py-2 rounded-lg border border-[#E3E8F0] bg-[#F7F9FC] text-[13px] focus:outline-none focus:border-[#1456F0] transition-colors">
                    {dossiersList.filter((d) => d !== "Tous").map((d) => <option key={d} value={d}>{d}</option>)}
                  </select>
                </div>
              </div>
              <div className="flex gap-2 pt-2">
                <button onClick={() => setShowAddModal(false)} className="flex-1 px-4 py-2 rounded-lg border border-[#D8DEE9] text-[#2B3242] hover:bg-[#F1F5F9] transition-colors text-[13px] font-medium">Annuler</button>
                <button onClick={handleAddDocument} className="flex-1 px-4 py-2 rounded-lg bg-[#1456F0] text-white text-[13px] font-medium hover:bg-[#0E3FC4] transition-colors">Ajouter</button>
              </div>
            </div>
          </div>
        </div>
      )}
    </main>
  );
}