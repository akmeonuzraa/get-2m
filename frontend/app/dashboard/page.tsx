import {
  profil,
  statistiques,
  documentsRecents,
  favoris,
  annonces,
  activiteRecente,
} from "@/data/dashboard";

export default function DashboardPage() {
  return (
    <main className="p-8 space-y-8">
      <div>
        <h1 className="text-3xl font-bold mb-1">Tableau de bord</h1>
        <p className="text-gray-600">Vue d'ensemble de votre activite</p>
      </div>

      <div className="bg-white border rounded-lg shadow p-4 flex items-center gap-4">
        <div className="w-14 h-14 rounded-full bg-blue-600 text-white flex items-center justify-center text-xl font-bold">
          {profil.nom.charAt(0)}
        </div>
        <div>
          <p className="font-semibold">{profil.nom}</p>
          <p className="text-sm text-gray-500">
            {profil.role} - {profil.service}
          </p>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        {statistiques.map((stat) => (
          <div
            key={stat.label}
            className="bg-white border rounded-lg shadow p-4 text-center"
          >
            <p className="text-3xl font-bold text-blue-600">{stat.valeur}</p>
            <p className="text-sm text-gray-500">{stat.label}</p>
          </div>
        ))}
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div className="bg-white border rounded-lg shadow p-4">
          <h2 className="font-semibold mb-3">Documents recents</h2>
          <ul className="space-y-2">
            {documentsRecents.map((doc) => (
              <li key={doc.id} className="text-sm flex justify-between">
                <span>{doc.nom}</span>
                <span className="text-gray-400">{doc.date}</span>
              </li>
            ))}
          </ul>
        </div>

        <div className="bg-white border rounded-lg shadow p-4">
          <h2 className="font-semibold mb-3">Documents favoris</h2>
          <ul className="space-y-2">
            {favoris.map((doc) => (
              <li key={doc.id} className="text-sm">
                {doc.nom}
              </li>
            ))}
          </ul>
        </div>

        <div className="bg-white border rounded-lg shadow p-4">
          <h2 className="font-semibold mb-3">Dernieres annonces</h2>
          <ul className="space-y-2">
            {annonces.map((a) => (
              <li key={a.id} className="text-sm flex justify-between">
                <span>{a.titre}</span>
                <span className="text-gray-400">{a.date}</span>
              </li>
            ))}
          </ul>
        </div>
      </div>

      <div className="bg-white border rounded-lg shadow p-4">
        <h2 className="font-semibold mb-3">Activite recente</h2>
        <ul className="space-y-3">
          {activiteRecente.map((act) => (
            <li
              key={act.id}
              className="text-sm flex justify-between border-b pb-2 last:border-0"
            >
              <span>Nouhaila {act.action}</span>
              <span className="text-gray-400">{act.date}</span>
            </li>
          ))}
        </ul>
      </div>
    </main>
  );
}
