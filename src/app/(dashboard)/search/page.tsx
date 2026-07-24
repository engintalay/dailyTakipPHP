"use client";

import { useState } from "react";
import { formatDateShort } from "@/lib/utils";

export default function SearchPage() {
  const [query, setQuery] = useState("");
  const [filterTag, setFilterTag] = useState("");
  const [filterUser, setFilterUser] = useState("");
  const [filterStart, setFilterStart] = useState("");
  const [filterEnd, setFilterEnd] = useState("");
  const [filterType, setFilterType] = useState("notes");
  const [results, setResults] = useState<any[]>([]);
  const [searched, setSearched] = useState(false);
  const [users, setUsers] = useState<any[]>([]);
  const [loading, setLoading] = useState(false);

  async function search() {
    setLoading(true);
    setSearched(true);

    if (users.length === 0) {
      try {
        const u = await fetch("/api/users").then((r) => r.json());
        setUsers(u);
      } catch {}
    }

    const params = new URLSearchParams();
    if (query) params.set("search", query);
    if (filterTag) params.set("tag", filterTag);
    if (filterUser) params.set("userId", filterUser);
    if (filterStart) params.set("startDate", filterStart);
    if (filterEnd) params.set("endDate", filterEnd);

    const res = await fetch(`/api/daily-notes?${params}`);
    const data = await res.json();
    setResults(data);
    setLoading(false);
  }

  function handleKeyDown(e: React.KeyboardEvent) {
    if (e.key === "Enter") search();
  }

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold">Arama</h1>

      <div className="bg-card border border-border rounded-xl p-5 space-y-4">
        <div className="flex gap-3">
          <input
            autoFocus
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            onKeyDown={handleKeyDown}
            className="flex-1 px-4 py-2.5 border border-border rounded-lg bg-background text-sm"
            placeholder="Notlarda ara..."
          />
          <button
            onClick={search}
            disabled={loading}
            className="px-6 py-2.5 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 disabled:bg-blue-400"
          >
            {loading ? "Aranıyor..." : "Ara"}
          </button>
        </div>

        <div className="flex flex-wrap gap-3 text-sm">
          <input
            type="date"
            value={filterStart}
            onChange={(e) => setFilterStart(e.target.value)}
            className="px-3 py-1.5 border border-border rounded-lg bg-background"
          />
          <input
            type="date"
            value={filterEnd}
            onChange={(e) => setFilterEnd(e.target.value)}
            className="px-3 py-1.5 border border-border rounded-lg bg-background"
          />
          <input
            value={filterTag}
            onChange={(e) => setFilterTag(e.target.value)}
            className="px-3 py-1.5 border border-border rounded-lg bg-background"
            placeholder="Etiket"
          />
          <button
            onClick={() => { setQuery(""); setFilterTag(""); setFilterStart(""); setFilterEnd(""); setResults([]); setSearched(false); }}
            className="px-3 py-1.5 text-muted-foreground hover:text-foreground border border-border rounded-lg"
          >
            Temizle
          </button>
        </div>
      </div>

      {searched && (
        <div className="space-y-3">
          <p className="text-sm text-muted-foreground">
            {results.length === 0
              ? "Sonuç bulunamadı."
              : `${results.length} sonuç bulundu.`}
          </p>
          {results.map((note: any) => (
            <div key={note.id} className="bg-card border border-border rounded-xl p-4">
              <div className="flex items-center gap-2 mb-1">
                <span className="text-sm font-medium">{note.user.name}</span>
                <span className="text-xs text-muted-foreground">·</span>
                <span className="text-xs text-muted-foreground">{formatDateShort(note.date)}</span>
              </div>
              <p className="text-sm whitespace-pre-wrap">{note.content}</p>
              {note.tags && (
                <div className="flex gap-1 mt-2">
                  {note.tags.split(",").filter(Boolean).map((tag: string) => (
                    <button
                      key={tag}
                      onClick={() => setFilterTag(tag.trim())}
                      className="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400"
                    >
                      {tag.trim()}
                    </button>
                  ))}
                </div>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
