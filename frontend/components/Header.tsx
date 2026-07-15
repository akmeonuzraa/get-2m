export default function Header() {
  return (
    <header className="flex justify-between items-center bg-white border-b p-4">
      <h1 className="text-xl font-semibold">Bienvenue, NOUHAILA</h1>
      <div className="flex items-center gap-3">
        <span className="text-sm text-gray-500">Utilisateur</span>
        <div className="w-9 h-9 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold">
          N
        </div>
      </div>
    </header>
  );
}
