"use client";

import { useState } from "react";
import {
  Search, Eye, X, FileText, FileSpreadsheet, File, FolderSearch,
  ChevronLeft, ChevronRight, Download, Share2, Calendar, User, Filter,
  Clock, CheckCircle, Archive, AlertCircle, Check, Copy, Mail,
  Link as LinkIcon, Users,
} from "lucide-react";

interface ResultatRecherche {
  id: number;
  nom: string;
  type: "PDF" | "DOCX" | "XLSX" | "PPTX";
  auteur: string;
  service: string;
  date: string;
  taille: string;
  description: string;
  status?: "validé" | "en-attente" | "archivé";
}

interface Toast {
  message: string;
  type: "success" | "error" | "info";
}

const resultatsRecherche: ResultatRecherche[] = [
  { id: 1, nom: "Rapport_Projet.pdf", type: "PDF", auteur: "nouhaila", service: "DSI", date: "08/07/2026", taille: "2.4 Mo", description: "Rapport final du projet", status: "validé" },
  { id: 2, nom: "Planning_Stage.docx", type: "DOCX", auteur: "kenza", service: "RH", date: "07/07/2026", taille: "856 Ko", description: "Planning des stages", status: "en-attente" },
  { id: 3, nom: "Budget_Previsionnel_2026.xlsx", type: "XLSX", auteur: "nizar", service: "Finance", date: "05/07/2026", taille: "1.8 Mo", description: "Budget prévisionnel 2026", status: "validé" },
  { id: 4, nom: "Contrat_Partenariat.pdf", type: "PDF", auteur: "ahmed", service: "Juridique", date: "03/07/2026", taille: "3.2 Mo", description: "Contrat de partenariat", status: "validé" },
  { id: 5, nom: "Note_Service_Interne.docx", type: "DOCX", auteur: "sara", service: "Administratif", date: "01/07/2026", taille: "512 Ko", description: "Note de service interne", status: "archivé" },
];

const services = ["Tous", "DSI", "RH", "Finance", "Juridique", "Administratif", "Technique"];

function getFileIcon(type: string) {
  if (type === "PDF") return <FileText className="w-4 h-4 text-[#E5392E]" />;
  if (type === "XLSX") return <FileSpreadsheet className="w-4 h-4 text-[#12A16A]" />;
  if (type === "PPTX") return <File className="w-4 h-4 text-[#E88A0C]" />;
  return <File className="w-4 h-4 text-[#1456F0]" />;
}

function getBadgeClasses(type: string) {
  if (type === "PDF") return "bg-[#E5392E]/10 text-[#E5392E]";
  if (type === "DOCX") return "bg-[#1456F0]/10 text-[#1456F0]";
  if (type === "XLSX") return "bg-[#12A16A]/10 text-[#12A16A]";
  return "bg-[#7C3AED]/10 text-[#7C3AED]";
}

function getStatusBadge(status?: string) {
  const statusMap: Record<string, { icon: typeof CheckCircle; color: string; label: string }> = {
    "validé": { icon: CheckCircle, color: "bg-[#12A16A]/10 text-[#12A16A] border-[#12A16A]/20", label: "Validé" },
    "en-attente": { icon: Clock, color: "bg-[#E88A0C]/10 text-[#E88A0C] border-[#E88A0C]/20", label: "En attente" },
    "archivé": { icon: Archive, color: "bg-[#E3E8F0] text-[#5C6A82] border-[#D8DEE9]", label: "Archivé" },
  };
  return statusMap[status || "en-attente"] || statusMap["en-attente"];
}

function Highlight({ text, query }: { text: string; query: string }) {
  if (!query.trim()) return <>{text}</>;
  const parts = text.split(new RegExp(`(${query})`, "gi"));
  return (
    <>
      {parts.map((part, i) =>
        part.toLowerCase() === query.toLowerCase() ? (
          <mark key={i} className="bg-[#1456F0]/30 text-[#0E1420] rounded px-0.5">{part}</mark>
        ) : (
          <span key={i}>{part}</span>
        )
      )}
    </>
  );
}

export default function RecherchePage() {
  const [search, setSearch] = useState("");
  const [typeFilter, setTypeFilter] = useState("Tous");
  const [serviceFilter, setServiceFilter] = useState("Tous");
  const [statusFilter, setStatusFilter] = useState("Tous");
  const [sortBy, setSortBy] = useState("date");
  const [page, setPage] = useState(1);
  const [detailDoc, setDetailDoc] = useState<ResultatRecherche | null>(null);
  const [showFilters, setShowFilters] = useState(false);
  const [toast, setToast] = useState<Toast | null>(null);
  const [shareDoc, setShareDoc] = useState<ResultatRecherche | null>(null);
  const [shareEmail, setShareEmail] = useState("");
  const [sharePermission, setSharePermission] = useState("read");
  const [copiedLink, setCopiedLink] = useState(false);
  const [isDownloading, setIsDownloading] = useState(false);
  const [downloadProgress, setDownloadProgress] = useState(0);

  const PAGE_SIZE = 5;
  const q = search.toLowerCase();

  const filtered = resultatsRecherche
    .filter((r) =>
      r.nom.toLowerCase().includes(q) ||
      r.auteur.toLowerCase().includes(q) ||
      r.service.toLowerCase().includes(q) ||
      r.type.toLowerCase().includes(q) ||
      r.description.toLowerCase().includes(q)
    )
    .filter((r) => typeFilter === "Tous" || r.type === typeFilter)
    .filter((r) => serviceFilter === "Tous" || r.service === serviceFilter)
    .filter((r) => statusFilter === "Tous" || r.status === statusFilter)
    .sort((a, b) => {
      if (sortBy === "nom") return a.nom.localeCompare(b.nom);
      if (sortBy === "auteur") return a.auteur.localeCompare(b.auteur);
      if (sortBy === "service") return a.service.localeCompare(b.service);
      return b.date.localeCompare(a.date);
    });

  const totalPages = Math.max(1, Math.ceil(filtered.length / PAGE_SIZE));
  const paginated = filtered.slice((page - 1) * PAGE_SIZE, page * PAGE_SIZE);

  const handleDownload = (doc: ResultatRecherche) => {
    setIsDownloading(true);
    setDownloadProgress(0);
    const interval = setInterval(() => {
      setDownloadProgress((prev) => {
        if (prev >= 100) {
          clearInterval(interval);
          setIsDownloading(false);
          setToast({ message: `"${doc.nom}" téléchargé avec succès`, type: "success" });
          setTimeout(() => setToast(null), 3000);
          return 100;
        }
        return prev + 10;
      });
    }, 200);
  };

  const handleShare = (doc: ResultatRecherche) => {
    setShareDoc(doc);
    setShareEmail("");
    setSharePermission("read");
  };

  const handleSendShare = () => {
    if (!shareEmail.trim()) {
      setToast({ message: "Veuillez entrer une adresse email", type: "error" });
      setTimeout(() => setToast(null), 3000);
      return;
    }
    setToast({ message: `Document partagé avec ${shareEmail}`, type: "success" });
    setShareDoc(null);
    setTimeout(() => setToast(null), 3000);
  };

  const handleCopyLink = () => {
    navigator.clipboard.writeText(`https://plateforme-2m.com/document/${shareDoc?.id}`);
    setCopiedLink(true);
    setTimeout(() => setCopiedLink(false), 2000);
  };

  const stats = {
    total: filtered.length,
    parType: {
      PDF: filtered.filter((r) => r.type === "PDF").length,
      DOCX: filtered.filter((r) => r.type === "DOCX").length,
      XLSX: filtered.filter((r) => r.type === "XLSX").length,
      PPTX: filtered.filter((r) => r.type === "PPTX").length,
    },
  };

  return (
    <main className="p-6 bg-[#F7F9FC] min-h-screen font-sans text-[#0E1420]">
      <div className="flex items-center justify-between mb-6">
        <div>
          <p className="text-[11px] font-semibold tracking-[0.14em] text-[#1456F0] uppercase mb-1.5">Recherche</p>
          <h1 className="text-[20px] font-semibold tracking-tight">Recherche documentaire</h1>
          <p className="text-[#5C6A82] text-[13px] mt-0.5">
            {filtered.length} résultat{filtered.length > 1 ? "s" : ""} trouvé{filtered.length > 1 ? "s" : ""}
          </p>
        </div>
        <button
          onClick={() => setShowFilters(!showFilters)}
          className={`px-3 py-1.5 rounded-lg flex items-center gap-2 text-[13px] font-medium transition-colors ${
            showFilters ? "bg-[#1456F0] text-white" : "bg-[#FFFFFF] border border-[#D8DEE9] text-[#2B3242] hover:border-[#C7D0DE]"
          }`}
        >
          <Filter className="w-3.5 h-3.5" />
          Filtres
          {showFilters && <X className="w-3.5 h-3.5" />}
        </button>
      </div>

      <div className="bg-[#FFFFFF] rounded-xl border border-[#E3E8F0] p-4 mb-4">
        <div className="flex flex-col md:flex-row gap-2">
          <div className="flex-1 relative">
            <Search className="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-[#7A869C]" />
            <input
              type="text"
              placeholder="Rechercher par nom, auteur, service, type ou description..."
              value={search}
              onChange={(e) => { setSearch(e.target.value); setPage(1); }}
              className="w-full bg-[#F7F9FC] border border-[#E3E8F0] rounded-lg py-2 pl-9 pr-3 text-[13px] focus:outline-none focus:border-[#1456F0] transition-colors placeholder:text-[#94A0B4]"
            />
          </div>
          <div className="flex gap-2 flex-wrap">
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
              <option value="service">Trier par service</option>
            </select>
          </div>
        </div>

        {showFilters && (
          <div className="mt-4 pt-4 border-t border-[#E3E8F0]">
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div>
                <label className="text-[11px] font-medium text-[#5C6A82] block mb-1.5">Service</label>
                <select value={serviceFilter} onChange={(e) => { setServiceFilter(e.target.value); setPage(1); }} className="w-full px-3 py-2 rounded-lg border border-[#E3E8F0] bg-[#F7F9FC] text-[13px] focus:outline-none focus:border-[#1456F0] transition-colors">
                  {services.map((s) => <option key={s} value={s}>{s}</option>)}
                </select>
              </div>
              <div>
                <label className="text-[11px] font-medium text-[#5C6A82] block mb-1.5">Statut</label>
                <select value={statusFilter} onChange={(e) => { setStatusFilter(e.target.value); setPage(1); }} className="w-full px-3 py-2 rounded-lg border border-[#E3E8F0] bg-[#F7F9FC] text-[13px] focus:outline-none focus:border-[#1456F0] transition-colors">
                  <option value="Tous">Tous les statuts</option>
                  <option value="validé">Validé</option>
                  <option value="en-attente">En attente</option>
                  <option value="archivé">Archivé</option>
                </select>
              </div>
              <div>
                <label className="text-[11px] font-medium text-[#5C6A82] block mb-1.5">Période</label>
                <input type="date" className="w-full px-3 py-2 rounded-lg border border-[#E3E8F0] bg-[#F7F9FC] text-[13px] focus:outline-none focus:border-[#1456F0] transition-colors" />
              </div>
            </div>
            <div className="flex justify-end gap-2 mt-3">
              <button
                onClick={() => { setServiceFilter("Tous"); setStatusFilter("Tous"); }}
                className="px-3 py-1.5 rounded-lg border border-[#D8DEE9] text-[#2B3242] hover:bg-[#F1F5F9] transition-colors text-[12px] font-medium"
              >
                Réinitialiser
              </button>
              <button
                onClick={() => setShowFilters(false)}
                className="px-3 py-1.5 rounded-lg bg-[#1456F0] text-white hover:bg-[#0E3FC4] transition-colors text-[12px] font-medium"
              >
                Appliquer
              </button>
            </div>
          </div>
        )}
      </div>

      {(search || typeFilter !== "Tous" || serviceFilter !== "Tous" || statusFilter !== "Tous") && (
        <div className="bg-[#FFFFFF] rounded-xl border border-[#E3E8F0] p-3 mb-4">
          <div className="flex flex-wrap items-center gap-3 text-[12px]">
            <span className="font-medium text-[#0E1420]">Résultats :</span>
            <span className="text-[#1456F0] font-semibold">{filtered.length}</span>
            <span className="text-[#C7D0DE]">·</span>
            <span className="text-[#5C6A82]">PDF: <span className="font-medium text-[#0E1420]">{stats.parType.PDF}</span></span>
            <span className="text-[#5C6A82]">DOCX: <span className="font-medium text-[#0E1420]">{stats.parType.DOCX}</span></span>
            <span className="text-[#5C6A82]">XLSX: <span className="font-medium text-[#0E1420]">{stats.parType.XLSX}</span></span>
            <span className="text-[#5C6A82]">PPTX: <span className="font-medium text-[#0E1420]">{stats.parType.PPTX}</span></span>
            {search && (
              <span className="ml-auto text-[#7A869C]">
                Recherche: "<span className="font-medium text-[#0E1420]">{search}</span>"
              </span>
            )}
          </div>
        </div>
      )}

      {filtered.length === 0 ? (
        <div className="bg-[#FFFFFF] border border-[#E3E8F0] rounded-xl p-12 text-center">
          <FolderSearch className="w-8 h-8 mx-auto mb-3 text-[#C7D0DE]" />
          <p className="font-medium text-[14px] mb-1">Aucun document trouvé</p>
          <p className="text-[13px] text-[#7A869C]">Essayez de modifier vos critères de recherche</p>
        </div>
      ) : (
        <div className="relative bg-[#FFFFFF] border border-[#E3E8F0] rounded-xl overflow-hidden">
          <span className="absolute top-0 left-0 right-0 h-[2px] bg-[#1456F0]" />
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-[#F1F5F9] border-b border-[#E3E8F0]">
                <tr>
                  <th className="p-3 text-left text-[#5C6A82] font-medium text-[12px]">Nom</th>
                  <th className="p-3 text-left text-[#5C6A82] font-medium text-[12px]">Type</th>
                  <th className="p-3 text-left text-[#5C6A82] font-medium text-[12px]">Service</th>
                  <th className="p-3 text-left text-[#5C6A82] font-medium text-[12px]">Auteur</th>
                  <th className="p-3 text-left text-[#5C6A82] font-medium text-[12px]">Date</th>
                  <th className="p-3 text-left text-[#5C6A82] font-medium text-[12px]">Statut</th>
                  <th className="p-3 text-center text-[#5C6A82] font-medium text-[12px]">Actions</th>
                </tr>
              </thead>
              <tbody>
                {paginated.map((doc) => {
                  const status = getStatusBadge(doc.status);
                  const StatusIcon = status.icon;
                  return (
                    <tr key={doc.id} className="border-t border-[#EAEEF3] hover:bg-[#F4F7FB] transition-colors">
                      <td className="p-3">
                        <div className="flex items-center gap-2.5">
                          {getFileIcon(doc.type)}
                          <span className="text-[13px] font-medium text-[#0E1420]">
                            <Highlight text={doc.nom} query={search} />
                          </span>
                        </div>
                      </td>
                      <td className="p-3"><span className={`px-2 py-0.5 rounded text-[11px] font-medium ${getBadgeClasses(doc.type)}`}>{doc.type}</span></td>
                      <td className="p-3 text-[#5C6A82] text-[13px]">
                        <span className="bg-[#F1F5F9] px-2 py-0.5 rounded text-[11px]">
                          <Highlight text={doc.service} query={search} />
                        </span>
                      </td>
                      <td className="p-3 text-[#5C6A82] text-[13px]"><Highlight text={doc.auteur} query={search} /></td>
                      <td className="p-3 text-[#5C6A82] font-mono text-[12px]">{doc.date}</td>
                      <td className="p-3">
                        <span className={`inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-medium border ${status.color}`}>
                          <StatusIcon className="w-3 h-3" />
                          {status.label}
                        </span>
                      </td>
                      <td className="p-3">
                        <div className="flex justify-center gap-0.5">
                          <button title="Voir" onClick={() => setDetailDoc(doc)} className="p-1.5 rounded-md hover:bg-[#E3E8F0] transition-colors">
                            <Eye className="w-3.5 h-3.5 text-[#5C6A82]" />
                          </button>
                          <button title="Télécharger" onClick={() => handleDownload(doc)} disabled={isDownloading} className="p-1.5 rounded-md hover:bg-[#E3E8F0] transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                            <Download className="w-3.5 h-3.5 text-[#5C6A82]" />
                          </button>
                          <button title="Partager" onClick={() => handleShare(doc)} className="p-1.5 rounded-md hover:bg-[#E3E8F0] transition-colors">
                            <Share2 className="w-3.5 h-3.5 text-[#5C6A82]" />
                          </button>
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {filtered.length > 0 && (
        <div className="flex justify-between items-center mt-5">
          <div className="text-[12px] text-[#7A869C]">
            Affichage de {(page - 1) * PAGE_SIZE + 1} à {Math.min(page * PAGE_SIZE, filtered.length)} sur {filtered.length} résultats
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

      {detailDoc && (
        <div className="fixed inset-0 bg-[#0E1420]/30 backdrop-blur-sm flex items-center justify-center z-50 p-4">
          <div className="bg-[#FFFFFF] rounded-xl shadow-2xl max-w-md w-full p-6 border border-[#D8DEE9]">
            <div className="flex justify-between items-center mb-5">
              <h3 className="text-[15px] font-semibold flex items-center gap-3">
                {getFileIcon(detailDoc.type)}
                {detailDoc.nom}
              </h3>
              <button onClick={() => setDetailDoc(null)} className="p-1.5 rounded-md hover:bg-[#E3E8F0] transition-colors">
                <X className="w-4 h-4 text-[#5C6A82]" />
              </button>
            </div>

            <div className="space-y-2.5">
              <div className="flex items-center gap-2 text-[13px] text-[#5C6A82]">
                <User className="w-3.5 h-3.5" />
                <span className="font-medium text-[#0E1420]">Auteur :</span>
                {detailDoc.auteur}
              </div>
              <div className="flex items-center gap-2 text-[13px] text-[#5C6A82]">
                <FolderSearch className="w-3.5 h-3.5" />
                <span className="font-medium text-[#0E1420]">Service :</span>
                {detailDoc.service}
              </div>
              <div className="flex items-center gap-2 text-[13px] text-[#5C6A82]">
                <Calendar className="w-3.5 h-3.5" />
                <span className="font-medium text-[#0E1420]">Date :</span>
                {detailDoc.date}
              </div>
              <div className="flex items-center gap-2 text-[13px] text-[#5C6A82]">
                <FileText className="w-3.5 h-3.5" />
                <span className="font-medium text-[#0E1420]">Taille :</span>
                {detailDoc.taille}
              </div>
              <div className="pt-3 border-t border-[#E3E8F0]">
                <p className="text-[13px] text-[#5C6A82]"><span className="font-medium text-[#0E1420]">Description :</span></p>
                <p className="text-[13px] text-[#2B3242] mt-1">{detailDoc.description}</p>
              </div>
              <div className="pt-1">
                <span className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded text-[12px] font-medium border ${getStatusBadge(detailDoc.status).color}`}>
                  {(() => {
                    const status = getStatusBadge(detailDoc.status);
                    const StatusIcon = status.icon;
                    return <><StatusIcon className="w-3.5 h-3.5" />{status.label}</>;
                  })()}
                </span>
              </div>
            </div>

            <div className="flex gap-2 mt-6">
              <button onClick={() => setDetailDoc(null)} className="flex-1 px-4 py-2 rounded-lg border border-[#D8DEE9] text-[#2B3242] hover:bg-[#F1F5F9] transition-colors text-[13px] font-medium">
                Fermer
              </button>
              <button
                onClick={() => handleDownload(detailDoc)}
                disabled={isDownloading}
                className="flex-1 px-4 py-2 rounded-lg bg-[#1456F0] text-white text-[13px] font-medium hover:bg-[#0E3FC4] transition-colors flex items-center justify-center gap-2"
              >
                {isDownloading ? (
                  <>
                    <div className="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin" />
                    {downloadProgress}%
                  </>
                ) : (
                  <><Download className="w-3.5 h-3.5" />Télécharger</>
                )}
              </button>
            </div>
          </div>
        </div>
      )}

      {shareDoc && (
        <div className="fixed inset-0 bg-[#0E1420]/30 backdrop-blur-sm flex items-center justify-center z-50 p-4">
          <div className="bg-[#FFFFFF] rounded-xl shadow-2xl max-w-md w-full p-6 border border-[#D8DEE9]">
            <div className="flex justify-between items-center mb-5">
              <h3 className="text-[15px] font-semibold flex items-center gap-2">
                <Share2 className="w-4 h-4 text-[#1456F0]" />
                Partager "{shareDoc.nom}"
              </h3>
              <button onClick={() => setShareDoc(null)} className="p-1.5 rounded-md hover:bg-[#E3E8F0] transition-colors">
                <X className="w-4 h-4 text-[#5C6A82]" />
              </button>
            </div>

            <div className="space-y-4">
              <div>
                <label className="text-[12px] font-medium text-[#2B3242] block mb-1.5">
                  <Mail className="w-3.5 h-3.5 inline mr-1.5 text-[#5C6A82]" />
                  Adresse email
                </label>
                <input
                  type="email"
                  placeholder="exemple@domaine.com"
                  value={shareEmail}
                  onChange={(e) => setShareEmail(e.target.value)}
                  className="w-full px-3 py-2 rounded-lg border border-[#E3E8F0] bg-[#F7F9FC] text-[13px] focus:outline-none focus:border-[#1456F0] transition-colors placeholder:text-[#94A0B4]"
                />
              </div>

              <div>
                <label className="text-[12px] font-medium text-[#2B3242] block mb-1.5">
                  <Users className="w-3.5 h-3.5 inline mr-1.5 text-[#5C6A82]" />
                  Permissions
                </label>
                <select
                  value={sharePermission}
                  onChange={(e) => setSharePermission(e.target.value)}
                  className="w-full px-3 py-2 rounded-lg border border-[#E3E8F0] bg-[#F7F9FC] text-[13px] focus:outline-none focus:border-[#1456F0] transition-colors"
                >
                  <option value="read">Lecture seule</option>
                  <option value="edit">Modification</option>
                  <option value="admin">Administration</option>
                </select>
              </div>

              <div>
                <label className="text-[12px] font-medium text-[#2B3242] block mb-1.5">
                  <LinkIcon className="w-3.5 h-3.5 inline mr-1.5 text-[#5C6A82]" />
                  Lien de partage
                </label>
                <div className="flex gap-2">
                  <input
                    type="text"
                    value={`https://plateforme-2m.com/document/${shareDoc.id}`}
                    readOnly
                    className="flex-1 px-3 py-2 rounded-lg border border-[#E3E8F0] bg-[#F7F9FC] text-[12px] text-[#5C6A82] cursor-default"
                  />
                  <button onClick={handleCopyLink} className="px-3 py-2 rounded-lg bg-[#1456F0] text-white hover:bg-[#0E3FC4] transition-colors flex items-center gap-1">
                    {copiedLink ? <Check className="w-3.5 h-3.5" /> : <Copy className="w-3.5 h-3.5" />}
                  </button>
                </div>
                {copiedLink && <p className="text-[11px] text-[#12A16A] mt-1">Lien copié</p>}
              </div>

              <div className="flex gap-2 pt-2">
                <button onClick={() => setShareDoc(null)} className="flex-1 px-4 py-2 rounded-lg border border-[#D8DEE9] text-[#2B3242] hover:bg-[#F1F5F9] transition-colors text-[13px] font-medium">
                  Annuler
                </button>
                <button onClick={handleSendShare} className="flex-1 px-4 py-2 rounded-lg bg-[#1456F0] text-white text-[13px] font-medium hover:bg-[#0E3FC4] transition-colors flex items-center justify-center gap-2">
                  <Share2 className="w-3.5 h-3.5" />
                  Partager
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {toast && (
        <div className="fixed bottom-8 right-8 z-50">
          <div className={`flex items-center gap-3 px-5 py-3 rounded-xl border shadow-2xl ${
            toast.type === "success" ? "bg-[#FFFFFF] border-[#12A16A]/30 text-[#12A16A]" :
            toast.type === "error" ? "bg-[#FFFFFF] border-[#E5392E]/30 text-[#E5392E]" :
            "bg-[#FFFFFF] border-[#1456F0]/30 text-[#1456F0]"
          }`}>
            {toast.type === "success" && <CheckCircle className="w-4 h-4" />}
            {toast.type === "error" && <AlertCircle className="w-4 h-4" />}
            <span className="text-[13px] font-medium">{toast.message}</span>
          </div>
        </div>
      )}
    </main>
  );
}