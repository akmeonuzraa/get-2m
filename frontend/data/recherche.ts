export interface ResultatRecherche {
  id: number;
  nom: string;
  type: "PDF" | "DOCX" | "XLSX";
  service: string;
  auteur: string;
  date: string;
  taille: string;
  description: string;
}

export const resultatsRecherche: ResultatRecherche[] = [
  {
    id: 1,
    nom: "Rapport_Projet.pdf",
    type: "PDF",
    service: "DSI",
    auteur: "nouhaila",
    date: "08/07/2026",
    taille: "2.3 MB",
    description: "Rapport de synthese du projet",
  },
  {
    id: 2,
    nom: "Planning_Stage.docx",
    type: "DOCX",
    service: "RH",
    auteur: "kenza",
    date: "07/07/2026",
    taille: "1.1 MB",
    description: "Planning des taches du stage",
  },
  {
    id: 3,
    nom: "Budget.xlsx",
    type: "XLSX",
    service: "Finance",
    auteur: "nizar",
    date: "05/07/2026",
    taille: "540 KB",
    description: "Suivi budgetaire du projet",
  },
];
