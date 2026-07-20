"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import type { ReactNode } from "react";
import {
  FileText, Folder, Users, Share2, Clock, Star, Bell, Upload, Plus, Search,
  ChevronRight, MoreHorizontal, Calendar, ArrowUpRight, Activity, Layers,
  GitBranch, Sparkles, Zap, Shield, FileCheck,
} from "lucide-react";

interface Stat {
  label: string;
  valeur: string;
  evolution: string;
  icon: typeof FileText;
}

interface DocRow {
  name: string;
  taille: string;
  date: string;
  status?: "completed" | "in-progress";
}

interface Announcement {
  title: string;
  date: string;
  priority: "high" | "medium";
  type: "meeting" | "update";
}

interface ActivityRow {
  action: string;
  date: string;
  time: string;
  type: "upload" | "join" | "edit";
}

interface CategoryRow {
  name: string;
  count: number;
  dot: string;
}

interface Shortcut {
  icon: typeof Upload;
  label: string;
  href: string;
}

const statistiques: Stat[] = [
  { label: "Documents", valeur: "1 248", evolution: "+12%", icon: FileText },
  { label: "Dossiers", valeur: "156", evolution: "+8%", icon: Folder },
  { label: "Utilisateurs", valeur: "24", evolution: "+4%", icon: Users },
  { label: "Partages actifs", valeur: "89", evolution: "+15%", icon: Share2 },
];

const documentsRecents: DocRow[] = [
  { name: "Rapport_Projet.pdf", taille: "2.4 Mo", date: "08/07/2026", status: "completed" },
  { name: "Planning_Stage.docx", taille: "856 Ko", date: "07/07/2026", status: "in-progress" },
];

const favoris: DocRow[] = [
  { name: "Cahier_des_charges.pdf", taille: "3.1 Mo", date: "08/07/2026" },
  { name: "Matrice_droits.xlsx", taille: "1.5 Mo", date: "07/07/2026" },
];

const annonces: Announcement[] = [
  { title: "Réunion équipe vendredi 10h", date: "07/07/2026", priority: "high", type: "meeting" },
  { title: "Mise à jour de la plateforme v2.3", date: "05/07/2026", priority: "medium", type: "update" },
];

const activiteRecente: ActivityRow[] = [
  { action: "a déposé Rapport_Projet.pdf", date: "08/07/2026", time: "10:30", type: "upload" },
  { action: "a rejoint l'espace DSI", date: "06/07/2026", time: "14:20", type: "join" },
  { action: "a modifié Planning_Stage.docx", date: "05/07/2026", time: "09:15", type: "edit" },
];

const categories: CategoryRow[] = [
  { name: "Administratif", count: 342, dot: "bg-[#1456F0]" },
  { name: "Ressources Humaines", count: 215, dot: "bg-[#12A16A]" },
  { name: "Financier", count: 187, dot: "bg-[#7C3AED]" },
  { name: "Juridique", count: 96, dot: "bg-[#E88A0C]" },
  { name: "Technique", count: 278, dot: "bg-[#E5392E]" },
  { name: "Autre", count: 130, dot: "bg-[#7A869C]" },
];

const shortcuts: Shortcut[] = [
  { icon: Upload, label: "Importer document", href: "/ged?action=upload" },
  { icon: Plus, label: "Créer dossier", href: "/ged" },
  { icon: Clock, label: "Documents récents", href: "/ged" },
  { icon: Search, label: "Recherche avancée", href: "/recherche" },
];

function Panel({ title, icon, action, children }: { title?: string; icon?: ReactNode; action?: ReactNode; children: ReactNode }) {
  return (
    <div className="bg-[#FFFFFF] rounded-xl p-5 border border-[#E3E8F0] hover:border-[#C7D0DE] transition-colors">
      {title && (
        <div className="flex items-center justify-between mb-4">
          <h3 className="text-[13px] font-medium text-[#0E1420] flex items-center gap-2 tracking-tight">{icon}{title}</h3>
          {action ?? (
            <button className="text-[12px] text-[#5C6A82] font-medium hover:text-[#0E1420] transition-colors">
              Voir tout
            </button>
          )}
        </div>
      )}
      {children}
    </div>
  );
}

export default function DashboardPage() {
  const router = useRouter();
  const [period, setPeriod] = useState("semaine");
  const usagePercent = 72;

  return (
    <div className="min-h-screen bg-[#F7F9FC] p-6 font-sans text-[#0E1420]">
      <div className="relative mb-6">
        <div className="relative bg-[#FFFFFF] border border-[#E3E8F0] rounded-xl p-7 flex justify-between items-start overflow-hidden">
          <span className="absolute top-0 left-0 right-0 h-[2px] bg-[#1456F0]" />
          <div>
            <p className="text-[11px] font-semibold tracking-[0.14em] text-[#1456F0] uppercase mb-2">Tableau de bord</p>
            <div className="flex items-center gap-2.5 mb-2">
              <div className="bg-[#1456F0]/10 p-1.5 rounded-lg">
                <Sparkles className="w-4 h-4 text-[#1456F0]" />
              </div>
              <h1 className="text-[22px] font-semibold tracking-tight">Bienvenue, Nouhaïla</h1>
            </div>
            <p className="text-[#5C6A82] text-[14px]">Voici un aperçu de votre espace documentaire</p>
            <div className="flex items-center gap-2 mt-4">
              <span className="bg-[#F1F5F9] border border-[#D8DEE9] px-3 py-1 rounded-md text-[12px] text-[#2B3242] flex items-center gap-1.5">
                Développeur Frontend
              </span>
              <span className="bg-[#F1F5F9] border border-[#D8DEE9] px-3 py-1 rounded-md text-[12px] text-[#2B3242] flex items-center gap-1.5">
                <Calendar className="w-3 h-3" /> DSI
              </span>
            </div>
          </div>
          <div className="flex items-center gap-2.5">
            <div className="w-9 h-9 rounded-md bg-[#1456F0] text-white flex items-center justify-center font-semibold text-[13px]">NC</div>
            <div>
              <p className="font-medium text-[13px]">Nouhaïla Chaaraoui</p>
              <p className="text-[11px] text-[#12A16A] flex items-center gap-1">
                <span className="w-1.5 h-1.5 rounded-full bg-[#12A16A]" /> En ligne
              </p>
            </div>
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        {statistiques.map((stat) => {
          const Icon = stat.icon;
          return (
            <div key={stat.label} className="relative bg-[#FFFFFF] rounded-xl p-5 pt-6 border border-[#E3E8F0] hover:border-[#C7D0DE] transition-colors flex items-start justify-between overflow-hidden">
              <span className="absolute top-0 left-0 right-0 h-[2px] bg-[#1456F0]" />
              <div>
                <p className="text-[26px] font-semibold tracking-tight tabular-nums">{stat.valeur}</p>
                <p className="text-[13px] text-[#5C6A82] mt-0.5">{stat.label}</p>
                <div className="flex items-center gap-1.5 mt-2.5">
                  <span className="inline-flex items-center gap-1 text-[11px] font-medium text-[#12A16A]">
                    <ArrowUpRight className="w-3 h-3" />{stat.evolution}
                  </span>
                  <span className="text-[11px] text-[#7A869C]">ce mois</span>
                </div>
              </div>
              <div className="p-2 rounded-lg bg-[#F1F5F9] border border-[#D8DEE9]">
                <Icon className="w-4 h-4 text-[#5C6A82]" />
              </div>
            </div>
          );
        })}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-3 mb-6">
        <div className="lg:col-span-2">
          <Panel
            title="Vue d'ensemble"
            icon={<Activity className="w-4 h-4 text-[#1456F0]" />}
            action={
              <div className="flex items-center gap-1 bg-[#F1F5F9] border border-[#D8DEE9] rounded-lg p-0.5">
                {["Semaine", "Mois", "Année"].map((p) => (
                  <button
                    key={p}
                    onClick={() => setPeriod(p.toLowerCase())}
                    className={`px-2.5 py-1 rounded-md text-[12px] font-medium transition-colors ${
                      period === p.toLowerCase() ? "bg-[#1456F0] text-white" : "text-[#5C6A82] hover:text-[#0E1420]"
                    }`}
                  >
                    {p}
                  </button>
                ))}
              </div>
            }
          >
            <div className="flex items-center gap-8">
              <div className="relative flex-shrink-0">
                <svg className="w-28 h-28 transform -rotate-90">
                  <circle className="text-[#E3E8F0]" strokeWidth="8" stroke="currentColor" fill="transparent" r="50" cx="56" cy="56" />
                  <circle
                    className="text-[#1456F0]"
                    strokeWidth="8"
                    strokeDasharray={2 * Math.PI * 50}
                    strokeDashoffset={2 * Math.PI * 50 * (1 - usagePercent / 100)}
                    strokeLinecap="round"
                    stroke="currentColor"
                    fill="transparent"
                    r="50"
                    cx="56"
                    cy="56"
                  />
                </svg>
                <div className="absolute inset-0 flex items-center justify-center flex-col">
                  <span className="text-[22px] font-semibold tabular-nums">{usagePercent}%</span>
                  <span className="text-[11px] text-[#7A869C]">utilisé</span>
                </div>
              </div>
              <div className="flex-1 space-y-2">
                {[
                  { label: "Espace utilisé", value: "72 Go", dot: "bg-[#1456F0]" },
                  { label: "Espace total", value: "100 Go", dot: "bg-[#C7D0DE]" },
                  { label: "Espace restant", value: "28 Go", dot: "bg-[#12A16A]" },
                ].map((row) => (
                  <div key={row.label} className="flex items-center justify-between py-1.5">
                    <span className="text-[13px] text-[#5C6A82] flex items-center gap-2">
                      <div className={`w-1.5 h-1.5 rounded-full ${row.dot}`} /> {row.label}
                    </span>
                    <span className="font-medium text-[13px]">{row.value}</span>
                  </div>
                ))}
                <button className="w-full mt-2 text-[12px] text-[#5C6A82] hover:text-[#0E1420] font-medium flex items-center justify-center gap-1.5 border border-[#D8DEE9] hover:border-[#C7D0DE] rounded-lg py-2 transition-colors">
                  Gérer l'espace <ChevronRight className="w-3.5 h-3.5" />
                </button>
              </div>
            </div>
          </Panel>
        </div>

        <Panel title="Catégories" icon={<Layers className="w-4 h-4 text-[#1456F0]" />}>
          <div className="space-y-1">
            {categories.map((cat) => (
              <div key={cat.name} className="flex items-center justify-between py-1.5 hover:bg-[#F1F5F9] px-2 -mx-2 rounded-md transition-colors">
                <div className="flex items-center gap-2.5">
                  <div className={`w-1.5 h-1.5 rounded-full ${cat.dot}`} />
                  <span className="text-[13px] text-[#2B3242]">{cat.name}</span>
                </div>
                <span className="text-[12px] text-[#7A869C]">{cat.count}</span>
              </div>
            ))}
          </div>
        </Panel>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6">
        <Panel title="Documents récents" icon={<Clock className="w-4 h-4 text-[#1456F0]" />}>
          <div className="space-y-1">
            {documentsRecents.map((doc) => (
              <div key={doc.name} className="flex items-center gap-3 py-2 px-2 -mx-2 hover:bg-[#F1F5F9] rounded-md transition-colors">
                <FileText className="w-4 h-4 text-[#5C6A82] shrink-0" />
                <div className="flex-1 min-w-0">
                  <p className="text-[13px] font-medium truncate">{doc.name}</p>
                  <div className="flex items-center gap-2 text-[11px] text-[#7A869C]">
                    <span>{doc.taille}</span><span>·</span><span>{doc.date}</span>
                  </div>
                </div>
                <div className={`w-1.5 h-1.5 rounded-full ${doc.status === "completed" ? "bg-[#12A16A]" : "bg-[#E88A0C]"}`} />
              </div>
            ))}
          </div>
        </Panel>

        <Panel title="Documents favoris" icon={<Star className="w-4 h-4 text-[#E88A0C]" />}>
          <div className="space-y-1">
            {favoris.map((doc) => (
              <div key={doc.name} className="flex items-center gap-3 py-2 px-2 -mx-2 hover:bg-[#F1F5F9] rounded-md transition-colors">
                <FileText className="w-4 h-4 text-[#5C6A82] shrink-0" />
                <div className="flex-1 min-w-0">
                  <p className="text-[13px] font-medium truncate">{doc.name}</p>
                  <div className="flex items-center gap-2 text-[11px] text-[#7A869C]">
                    <span>{doc.taille}</span><span>·</span><span>{doc.date}</span>
                  </div>
                </div>
                <Star className="w-3.5 h-3.5 text-[#E88A0C] fill-[#E88A0C]" />
              </div>
            ))}
          </div>
        </Panel>

        <Panel title="Dernières annonces" icon={<Bell className="w-4 h-4 text-[#1456F0]" />}>
          <div className="space-y-1">
            {annonces.map((ann) => (
              <div key={ann.title} className="flex items-start gap-3 py-2 px-2 -mx-2 hover:bg-[#F1F5F9] rounded-md transition-colors">
                {ann.type === "meeting" ? (
                  <Users className="w-4 h-4 text-[#5C6A82] mt-0.5 shrink-0" />
                ) : (
                  <GitBranch className="w-4 h-4 text-[#5C6A82] mt-0.5 shrink-0" />
                )}
                <div className="flex-1">
                  <p className="text-[13px] font-medium">{ann.title}</p>
                  <div className="flex items-center gap-2 mt-1">
                    <span className={`text-[10px] px-1.5 py-0.5 rounded ${ann.priority === "high" ? "bg-[#E5392E]/10 text-[#E5392E]" : "bg-[#1456F0]/10 text-[#1456F0]"}`}>
                      {ann.priority === "high" ? "Urgent" : "Important"}
                    </span>
                    <span className="text-[11px] text-[#7A869C]">{ann.date}</span>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </Panel>
      </div>

      <div className="mb-6">
        <Panel
          title="Activité récente"
          icon={<Activity className="w-4 h-4 text-[#1456F0]" />}
          action={<MoreHorizontal className="w-4 h-4 text-[#7A869C] cursor-pointer hover:text-[#0E1420]" />}
        >
          <div className="space-y-2">
            {activiteRecente.map((act, idx) => (
              <div key={idx} className="flex items-start gap-3">
                <div className={`w-1.5 h-1.5 mt-2 rounded-full ${act.type === "upload" ? "bg-[#1456F0]" : act.type === "join" ? "bg-[#12A16A]" : "bg-[#E88A0C]"}`} />
                <div className="flex-1 bg-[#F1F5F9] border border-[#E3E8F0] rounded-lg p-3 flex items-center justify-between">
                  <div>
                    <p className="text-[13px]">
                      <span className="font-medium">Nouhaïla</span>{" "}
                      <span className="text-[#5C6A82]">{act.action}</span>
                    </p>
                    <div className="flex items-center gap-3 mt-1 text-[11px] text-[#7A869C]">
                      <span className="flex items-center gap-1"><Calendar className="w-3 h-3" />{act.date}</span>
                      <span className="flex items-center gap-1"><Clock className="w-3 h-3" />{act.time}</span>
                    </div>
                  </div>
                  <span className="px-2 py-0.5 rounded text-[11px] font-medium bg-[#E3E8F0] text-[#2B3242]">
                    {act.type === "upload" ? "Téléversé" : act.type === "join" ? "Rejoint" : "Modifié"}
                  </span>
                </div>
              </div>
            ))}
          </div>
        </Panel>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-3">
        <div className="lg:col-span-2">
          <Panel title="Raccourcis rapides" icon={<Zap className="w-4 h-4 text-[#1456F0]" />} action={<span />}>
            <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
              {shortcuts.map((item) => {
                const Icon = item.icon;
                return (
                  <button
                    key={item.label}
                    onClick={() => router.push(item.href)}
                    className="flex flex-col items-center gap-2 p-4 bg-[#F1F5F9] border border-[#E3E8F0] rounded-lg hover:border-[#C7D0DE] transition-colors"
                  >
                    <Icon className="w-4 h-4 text-[#5C6A82]" />
                    <span className="text-[12px] font-medium text-[#2B3242] text-center">{item.label}</span>
                  </button>
                );
              })}
            </div>
          </Panel>
        </div>

        <div className="bg-[#FFFFFF] border border-[#E3E8F0] rounded-xl p-5 flex flex-col justify-center space-y-4">
          {[
            { icon: Shield, title: "Accès partout", sub: "Sécurisé et disponible" },
            { icon: Users, title: "Collaboration efficace", sub: "Travaillez en équipe" },
            { icon: FileCheck, title: "Conformité garantie", sub: "Normes et réglementations" },
          ].map((f) => (
            <div key={f.title} className="flex items-center gap-3">
              <div className="bg-[#F1F5F9] border border-[#D8DEE9] p-2 rounded-lg">
                <f.icon className="w-4 h-4 text-[#1456F0]" />
              </div>
              <div>
                <p className="font-medium text-[13px]">{f.title}</p>
                <p className="text-[11px] text-[#7A869C]">{f.sub}</p>
              </div>
            </div>
          ))}
        </div>
      </div>

      <div className="mt-8 text-center text-[12px] text-[#94A0B4] border-t border-[#E3E8F0] pt-6">
        <p>© 2026 Plateforme-2M — Tous droits réservés</p>
      </div>
    </div>
  );
}