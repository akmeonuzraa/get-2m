"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";

export default function Sidebar() {
  const pathname = usePathname();

  const menu = [
    { label: "Tableau de bord", href: "/dashboard" },
    { label: "GED", href: "/ged" },
    { label: "Recherche", href: "/recherche" },
  ];

  return (
    <aside className="w-60 bg-[#14213D] text-white min-h-screen p-5 flex flex-col">
      <h2 className="text-lg font-bold mb-8 tracking-tight">
        Plateforme-2M
      </h2>

      <nav className="space-y-1">
        {menu.map((item) => {
          const active = pathname === item.href;

          return (
            <Link
              key={item.href}
              href={item.href}
              className={`relative block pl-4 pr-3 py-2.5 text-sm rounded-r-md transition-colors ${
                active
                  ? "bg-white/5 text-white"
                  : "text-white/60 hover:text-white hover:bg-white/5"
              }`}
            >
              {active && (
                <span className="absolute left-0 top-1 bottom-1 w-[3px] rounded-full bg-[#0E7C86]" />
              )}
              {item.label}
            </Link>
          );
        })}
      </nav>
    </aside>
  );
}