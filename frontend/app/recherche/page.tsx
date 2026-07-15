"use client";

import { useState } from "react";
import { Search, Eye, X, FileText, FileSpreadsheet, File, FolderSearch } from "lucide-react";
import { resultatsRecherche, ResultatRecherche } from "@/data/recherche";

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

function Highlight({ text, query }: { text: string; query: string }) {
  if (!query.trim()) return <>{text}</>;
  const parts = text.split(new RegExp(`(${query})`, "gi"));
  return (
    <>
      {parts.map((part, i) =>
        part.toLowerCase() === query.toLowerCase() ? (
          <mark key={i} className="bg-yellow-200 rounded px-0.5">{part}</mark>
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
  const [sortBy, setSortBy] = useState("date");
  const [detailDoc, setDetailDoc] = useState<ResultatRecherche | null>(null);

  const q = search.toLowerCase();

  const filtered = resultatsRecherche
    .filter(
      (r) =>
        r.nom.toLowerCase().includes(q) ||
        r.auteur.toLowerCase().includes(q) ||
        r.service.toLowerCase().includes(q) ||
        r.type.toLowerCase().includes(q)
    )
    .filter((r) => typeFilter === "Tous" || r.type === typeFilter)
    .sort((a, b) => {
      if (sortBy === "nom") return a.nom.localeCompare(b.nom);
      if (sortBy === "auteur") return a.auteur.localeCompare(b.auteur);
      return b.date.localeCompare(a.date);
    });

  return (
    <main className="p-8">
      <h1 className="text-3xl font-bold mb-1">Recherche documentaire</h1>
      <p className="text-gray-600 mb-6">
        Retrouvez rapidement vos documents par nom, auteur, service ou type.
      </p>

      <div className="flex flex-col md:flex-row gap-3 mb-4">
        <div className="flex-1 relative">
          <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
          <input
            type="text"
            placeholder="Rechercher par nom, auteur, service ou type..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full border rounded-lg p-3 pl-10"
          />
        </div>
        <select
          value={typeFilter}
          onChange={(e) => setTypeFilter(e.target.value)}
          className="border rounded-lg p-3"
        >
          <option value="Tous">Tous les types</option>
          <option value="PDF">PDF</option>
          <option value="DOCX">DOCX</option>
          <option value="XLSX">XLSX</option>
        </select>
        <select
          value={sortBy}
          onChange={(e) => setSortBy(e.target.value)}
          className="border rounded-lg p-3"
        >
          <option value="date">Trier par date</option>
          <option value="nom">Trier par nom</option>
          <option value="auteur">Trier par auteur</option>
        </select>
      </div>

      <p className="text-sm text-gray-500 mb-3">
        {filtered.length} résultat{filtered.length > 1 ? "s" : ""}
      </p>

      {filtered.length === 0? (
        <div className="bg-white border rounded-lg shadow p-12 text-center text-gray-500">
          <FolderSearch className="w-12 h-12 mx-auto mb-3 text-gray-300" />
          <p className="font-medium">Aucun document trouvé.</p>
        </div>
      ) : (
        <div className="bg-white rounded-lg shadow overflow-hidden">
          <table className="w-full">
            <thead className="bg-gray-100">
              <tr>
                <th className="p-3 text-left">Nom</th>
                <th className="p-3 text-left">Type</th>
                <th className="p-3 text-left">Service</th>
                <th className="p-3 text-left">Auteur</th>
                <th className="p-3 text-left">Date</th>
                <th className="p-3 text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              {filtered.map((r) => (
                <tr key={r.id} className="border-t hover:bg-gray-50">
                  <td className="p-3 flex items-center gap-2">
                    {getFileIcon(r.type)}
                    <Highlight text={r.nom} query={search} />
                  </td>
                  <td className="p-3">
                    <span className={`px-2 py-1 rounded text-xs font-medium ${getBadgeClasses(r.type)}`}>
                      {r.type}
                    </span>
                  </td>
                  <td className="p-3">
                    <Highlight text={r.service} query={search} />
                  </td>
                  <td className="p-3">
                    <Highlight text={r.auteur} query={search} />
                  </td>
                  <td className="p-3">{r.date}</td>
                  <td className="p-3 text-center">
                    <button
                      title="Voir"
                      onClick={() => setDetailDoc(r)}
                      className="p-2 rounded hover:bg-gray-100 inline-flex"
                    >
                      <Eye className="w-4 h-4 text-gray-600" />
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {detailDoc && (
        <div className="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
            <div className="flex justify-between items-center mb-4">
              <h2 className="text-lg font-semibold flex items-center gap-2">
                {getFileIcon(detailDoc.type)}
                {detailDoc.nom}
              </h2>
              <button onClick={() => setDetailDoc(null)}>
                <X className="w-5 h-5 text-gray-500" />
              </button>
            </div>
            <p className="text-sm text-gray-600 mb-4">{detailDoc.description}</p>
            <div className="text-sm space-y-1">
              <p><span className="text-gray-500">Service :</span> {detailDoc.service}</p>
              <p><span className="text-gray-500">Auteur :</span> {detailDoc.auteur}</p>
              <p><span className="text-gray-500">Taille :</span> {detailDoc.taille}</p>
              <p><span className="text-gray-500">Date :</span> {detailDoc.date}</p>
            </div>
          </div>
        </div>
      )}
    </main>
  );
}