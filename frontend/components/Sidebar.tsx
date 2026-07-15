export default function Sidebar() {
  const menu = [
    { label: "Tableau de bord", href: "/dashboard" },
    { label: "GED", href: "/ged" },
    { label: "Recherche", href: "/recherche" },
  ];

  return (
    <aside className="w-56 bg-gray-900 text-white min-h-screen p-4">
      <h2 className="text-lg font-bold mb-6">Plateforme-2M</h2>
      <nav className="space-y-2">
        {menu.map((item) => (
          <a
            key={item.href}
            href={item.href}
            className="block px-3 py-2 rounded hover:bg-gray-700 text-sm"
          >
            {item.label}
          </a>
        ))}
      </nav>
    </aside>
  );
}
