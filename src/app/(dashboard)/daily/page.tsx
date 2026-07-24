"use client";

import { useState, useEffect } from "react";
import { useSession } from "next-auth/react";
import { useRouter } from "next/navigation";
import { formatDateShort, formatDateOnly } from "@/lib/utils";
import type { SessionUser } from "@/lib/types";

type Note = {
  id: string;
  userId: string;
  date: string;
  content: string;
  tags: string;
  user: { id: string; name: string; email: string };
};

export default function DailyNotesPage() {
  const { data: session } = useSession();
  const router = useRouter();
  const user = session?.user as SessionUser | undefined;

  const [notes, setNotes] = useState<Note[]>([]);
  const [loading, setLoading] = useState(true);
  const [showAdd, setShowAdd] = useState(false);

  const [content, setContent] = useState("");
  const [tags, setTags] = useState("");
  const [noteDate, setNoteDate] = useState(formatDateOnly(new Date()));

  const [filterUser, setFilterUser] = useState("");
  const [filterTag, setFilterTag] = useState("");
  const [filterStart, setFilterStart] = useState("");
  const [filterEnd, setFilterEnd] = useState("");

  useEffect(() => {
    loadNotes();
  }, [filterUser, filterTag, filterStart, filterEnd]);

  async function loadNotes() {
    setLoading(true);
    const params = new URLSearchParams();
    if (filterUser) params.set("userId", filterUser);
    if (filterTag) params.set("tag", filterTag);
    if (filterStart) params.set("startDate", filterStart);
    if (filterEnd) params.set("endDate", filterEnd);

    const res = await fetch(`/api/daily-notes?${params}`);
    const data = await res.json();
    setNotes(data);
    setLoading(false);
  }

  async function addNote(e: React.FormEvent) {
    e.preventDefault();
    if (!content.trim()) return;
    await fetch("/api/daily-notes", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ date: noteDate, content: content.trim(), tags: tags.trim() }),
    });
    setContent("");
    setTags("");
    setNoteDate(formatDateOnly(new Date()));
    setShowAdd(false);
    loadNotes();
  }

  async function deleteNote(id: string) {
    if (!confirm("Bu notu silmek istediğinize emin misiniz?")) return;
    await fetch(`/api/daily-notes/${id}`, { method: "DELETE" });
    loadNotes();
  }

  const isAdmin = user?.role === "ADMIN";

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">Daily Notlar</h1>
        <button
          onClick={() => setShowAdd(!showAdd)}
          className="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700"
        >
          + Not Ekle
        </button>
      </div>

      {showAdd && (
        <form onSubmit={addNote} className="bg-card border border-border rounded-xl p-5 space-y-4">
          <div>
            <label className="block text-sm font-medium mb-1">Tarih</label>
            <input
              type="date"
              value={noteDate}
              onChange={(e) => setNoteDate(e.target.value)}
              className="w-full px-3 py-2 border border-border rounded-lg bg-background"
            />
          </div>
          <div>
            <label className="block text-sm font-medium mb-1">Not</label>
            <textarea
              required
              rows={3}
              value={content}
              onChange={(e) => setContent(e.target.value)}
              className="w-full px-3 py-2 border border-border rounded-lg bg-background resize-none"
              placeholder="Bugün ne yaptın? ..."
            />
          </div>
          <div>
            <label className="block text-sm font-medium mb-1">Etiketler (virgülle ayır)</label>
            <input
              value={tags}
              onChange={(e) => setTags(e.target.value)}
              className="w-full px-3 py-2 border border-border rounded-lg bg-background"
              placeholder="frontend, bugfix, toplantı"
            />
          </div>
          <button
            type="submit"
            className="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700"
          >
            Kaydet
          </button>
        </form>
      )}

      <div className="bg-card border border-border rounded-xl p-4 space-y-3">
        <div className="flex flex-wrap gap-3">
          <input
            type="date"
            value={filterStart}
            onChange={(e) => setFilterStart(e.target.value)}
            className="px-3 py-1.5 border border-border rounded-lg bg-background text-sm"
            placeholder="Başlangıç"
          />
          <input
            type="date"
            value={filterEnd}
            onChange={(e) => setFilterEnd(e.target.value)}
            className="px-3 py-1.5 border border-border rounded-lg bg-background text-sm"
            placeholder="Bitiş"
          />
          <input
            value={filterTag}
            onChange={(e) => setFilterTag(e.target.value)}
            className="px-3 py-1.5 border border-border rounded-lg bg-background text-sm"
            placeholder="Etiket ara..."
          />
          <button
            onClick={() => { setFilterUser(""); setFilterTag(""); setFilterStart(""); setFilterEnd(""); }}
            className="px-3 py-1.5 text-sm text-muted-foreground hover:text-foreground"
          >
            Temizle
          </button>
        </div>
      </div>

      <div className="space-y-3">
        {loading ? (
          <p className="text-muted-foreground">Yükleniyor...</p>
        ) : notes.length === 0 ? (
          <p className="text-muted-foreground">Henüz not eklenmemiş.</p>
        ) : (
          notes.map((note) => (
            <div key={note.id} className="bg-card border border-border rounded-xl p-4">
              <div className="flex items-center justify-between mb-2">
                <div className="flex items-center gap-2">
                  <div className="w-7 h-7 rounded-full bg-blue-600 flex items-center justify-center text-white text-xs font-bold">
                    {note.user.name.charAt(0)}
                  </div>
                  <span className="text-sm font-medium">{note.user.name}</span>
                  <span className="text-xs text-muted-foreground">·</span>
                  <span className="text-xs text-muted-foreground">{formatDateShort(note.date)}</span>
                </div>
                {(isAdmin || note.userId === user?.id) && (
                  <button
                    onClick={() => deleteNote(note.id)}
                    className="text-xs text-muted-foreground hover:text-red-500"
                  >
                    Sil
                  </button>
                )}
              </div>
              <p className="text-sm whitespace-pre-wrap">{note.content}</p>
              {note.tags && (
                <div className="flex gap-1 mt-2">
                  {note.tags.split(",").filter(Boolean).map((tag) => (
                    <button
                      key={tag}
                      onClick={() => setFilterTag(tag.trim())}
                      className="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 hover:bg-blue-200 dark:bg-blue-900/30 dark:text-blue-400"
                    >
                      {tag.trim()}
                    </button>
                  ))}
                </div>
              )}
            </div>
          ))
        )}
      </div>
    </div>
  );
}
