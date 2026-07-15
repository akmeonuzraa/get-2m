export interface Version {
  numero: string;
  date: string;
  auteur: string;
}

export interface DocumentItem {
  id: number;
  nom: string;
  type: "PDF" | "DOCX" | "XLSX";
  auteur: string;
  date: string;
  taille: string;
  dossier: string;
  description: string;
  supprime: boolean;
  versions: Version[];
}

export const dossiers = ["Tous", "Rapports", "RH", "Finance"];

export const documents: DocumentItem[] = [
  {
    id: 1,
    nom: "Rapport_Projet.pdf",
    type: "PDF",
    auteur: "nouhaila",
    date: "08/07/2026",
    taille: "2.3 MB",
    dossier: "Rapports",
    description: "Rapport de synthese du projet",
    supprime: false,
    versions: [
      { numero: "v2", date: "08/07/2026", auteur: "nouhaila" },
      { numero: "v1", date: "01/07/2026", auteur: "nouhaila" },
    ],
  },
  {
    id: 2,
    nom: "Planning_Stage.docx",
    type: "DOCX",
    auteur: "kenza",
    date: "07/07/2026",
    taille: "1.1 MB",
    dossier: "RH",
    description: "Planning des taches du stage",
    supprime: false,
    versions: [{ numero: "v1", date: "07/07/2026", auteur: "kenza" }],
  },
  {
    id: 3,
    nom: "Budget.xlsx",
    type: "XLSX",
    auteur: "nizar",
    date: "05/07/2026",
    taille: "540 KB",
    dossier: "Finance",
    description: "Suivi budgetaire du projet",
    supprime: false,
    versions: [{ numero: "v1", date: "05/07/2026", auteur: "nizar" }],
  },
];
