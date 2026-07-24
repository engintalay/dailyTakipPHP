"use client";

import { useState, useEffect, useRef } from "react";
import { useSession } from "next-auth/react";
import { useRouter } from "next/navigation";
import { formatDateShort, formatDateOnly } from "@/lib/utils";
import type { SessionUser } from "@/lib/types";

type UploadedFile = {
  name: string;
  url: string;
  size: number;
  type: string;
};

type Note = {
  id: string;
  userId: string;
  date: string;
  content: string;
  tags: string;
  jiraLink: string;
  files: string;
  user: { id: string; name: string; email: string };
};

export default function DailyNotesPage() {
  const { data: session } = useSession();
  const router = useRouter();
  const user = session?.user as SessionUser | undefined;

  const [notes, setNotes] = useState<Note[]>([]);
  const [loading, setLoading] = useState(true);
  const [showAdd, setShowAdd] = useState(false);
  const [users, setUsers] = useState<{ id: string; name: string }[]>([]);

  const [content, setContent] = useState("");
  const [tags, setTags] = useState("");
  const [jiraLink, setJiraLink] = useState("");
  const [noteDate, setNoteDate] = useState(formatDateOnly(new Date()));
  const [noteUserId, setNoteUserId] = useState("");
  const [attachedFiles, setAttachedFiles] = useState<File[]>([]);
  const [uploading, setUploading] = useState(false);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const [filterUser, setFilterUser] = useState("");
  const [filterTag, setFilterTag] = useState("");
  const [filterStart, setFilterStart] = useState("");
  const [filterEnd, setFilterEnd] = useState("");
  const [showMissing, setShowMissing] = useState(false);
  const [missingUsers, setMissingUsers] = useState<{ id: string; name: string }[]>([]);
  const [loadingMissing, setLoadingMissing] = useState(false);

  useEffect(() => {
    loadNotes();
    if (user?.role === "ADMIN") loadUsers();
  }, [filterUser, filterTag, filterStart, filterEnd]);

  useEffect(() => {
    if (showMissing) checkMissing();
  }, [showMissing, filterStart, filterEnd]);

  async function checkMissing() {
    setLoadingMissing(true);
    const today = formatDateOnly(new Date());
    const start = filterStart || today;
    const end = filterEnd || today;

    const [usersRes, notesRes] = await Promise.all([
      fetch("/api/users"),
      fetch(`/api/daily-notes?startDate=${start}&endDate=${end}`),
    ]);

    const allUsers: { id: string; name: string }[] = await usersRes.json();
    const allNotes: Note[] = await notesRes.json();

    const userIdsWithNotes = new Set(allNotes.map((n) => n.userId));
    const missing = allUsers.filter((u) => !userIdsWithNotes.has(u.id));
    setMissingUsers(missing);
    setLoadingMissing(false);
  }

  async function loadUsers() {
    const res = await fetch("/api/users");
    const data = await res.json();
    setUsers(data);
  }

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

    let uploadedFiles: UploadedFile[] = [];

    if (attachedFiles.length > 0) {
      setUploading(true);
      for (const file of attachedFiles) {
        const fd = new FormData();
        fd.set("file", file);
        const res = await fetch("/api/upload", { method: "POST", body: fd });
        const data = await res.json();
        uploadedFiles.push(data);
      }
      setUploading(false);
    }

    const body: any = {
      date: noteDate,
      content: content.trim(),
      tags: tags.trim(),
      jiraLink: jiraLink.trim(),
      files: JSON.stringify(uploadedFiles),
    };
    if (noteUserId) body.userId = noteUserId;

    await fetch("/api/daily-notes", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(body),
    });

    setContent("");
    setTags("");
    setJiraLink("");
    setNoteDate(formatDateOnly(new Date()));
    setNoteUserId("");
    setAttachedFiles([]);
    setShowAdd(false);
    loadNotes();
  }

  async function deleteNote(id: string) {
    if (!confirm("Bu notu silmek istediğinize emin misiniz?")) return;
    await fetch(`/api/daily-notes/${id}`, { method: "DELETE" });
    loadNotes();
  }

  function formatFileSize(bytes: number) {
    if (bytes < 1024) return bytes + " B";
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + " KB";
    return (bytes / (1024 * 1024)).toFixed(1) + " MB";
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
          {isAdmin && (
            <div>
              <label className="block text-sm font-medium mb-1">Kullanıcı</label>
              <select
                value={noteUserId}
                onChange={(e) => setNoteUserId(e.target.value)}
                className="w-full px-3 py-2 border border-border rounded-lg bg-background"
              >
                <option value="">Kendim</option>
                {users.map((u) => (
                  <option key={u.id} value={u.id}>{u.name}</option>
                ))}
              </select>
            </div>
          )}
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
          <div>
            <label className="block text-sm font-medium mb-1">Jira Linki</label>
            <input
              value={jiraLink}
              onChange={(e) => setJiraLink(e.target.value)}
              className="w-full px-3 py-2 border border-border rounded-lg bg-background"
              placeholder="https://jira.company.com/browse/PROJ-123"
            />
          </div>
          <div>
            <label className="block text-sm font-medium mb-1">Dosya Ekle</label>
            <input
              ref={fileInputRef}
              type="file"
              multiple
              onChange={(e) => setAttachedFiles(Array.from(e.target.files || []))}
              className="w-full text-sm file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900/20 dark:file:text-blue-400"
            />
            {attachedFiles.length > 0 && (
              <div className="mt-2 space-y-1">
                {attachedFiles.map((f, i) => (
                  <div key={i} className="flex items-center gap-2 text-xs text-muted-foreground">
                    <span>📎 {f.name}</span>
                    <span>({formatFileSize(f.size)})</span>
                    <button
                      type="button"
                      onClick={() => setAttachedFiles(attachedFiles.filter((_, j) => j !== i))}
                      className="text-red-500 hover:text-red-700"
                    >
                      ×
                    </button>
                  </div>
                ))}
              </div>
            )}
          </div>
          <button
            type="submit"
            disabled={uploading}
            className="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 disabled:bg-blue-400"
          >
            {uploading ? "Dosyalar yükleniyor..." : "Kaydet"}
          </button>
        </form>
      )}

      <div className="bg-card border border-border rounded-xl p-4 space-y-3">
        <div className="flex flex-wrap gap-3 items-center">
          <input
            type="date"
            value={filterStart}
            onChange={(e) => setFilterStart(e.target.value)}
            className="px-3 py-1.5 border border-border rounded-lg bg-background text-sm"
          />
          <input
            type="date"
            value={filterEnd}
            onChange={(e) => setFilterEnd(e.target.value)}
            className="px-3 py-1.5 border border-border rounded-lg bg-background text-sm"
          />
          <input
            value={filterTag}
            onChange={(e) => setFilterTag(e.target.value)}
            className="px-3 py-1.5 border border-border rounded-lg bg-background text-sm"
            placeholder="Etiket ara..."
          />
          <button
            onClick={() => { setShowMissing(!showMissing); }}
            className={`px-3 py-1.5 text-sm rounded-lg border transition-colors ${
              showMissing
                ? "bg-amber-500 text-white border-amber-500"
                : "border-border text-muted-foreground hover:text-foreground"
            }`}
          >
            ⏳ Eksik Notlar
          </button>
          <button
            onClick={() => { setFilterUser(""); setFilterTag(""); setFilterStart(""); setFilterEnd(""); setShowMissing(false); }}
            className="px-3 py-1.5 text-sm text-muted-foreground hover:text-foreground"
          >
            Temizle
          </button>
        </div>
      </div>

      {showMissing && (
        <div className="bg-card border border-border rounded-xl p-5">
          <h2 className="font-semibold mb-3">⏳ Not Girmeyen Kullanıcılar</h2>
          {loadingMissing ? (
            <p className="text-sm text-muted-foreground">Kontrol ediliyor...</p>
          ) : missingUsers.length === 0 ? (
            <div className="flex items-center gap-2 text-sm text-emerald-600">
              <span>✅</span>
              <span>Seçilen tarih aralığında herkes notunu girmiş.</span>
            </div>
          ) : (
            <div className="space-y-2">
              <p className="text-sm text-muted-foreground mb-2">
                {missingUsers.length} kullanıcı not girmemiş:
              </p>
              {missingUsers.map((u) => (
                <div key={u.id} className="flex items-center justify-between px-3 py-2 rounded-lg bg-amber-50 dark:bg-amber-950/20">
                  <div className="flex items-center gap-2">
                    <div className="w-7 h-7 rounded-full bg-amber-500 flex items-center justify-center text-white text-xs font-bold">
                      {u.name.charAt(0)}
                    </div>
                    <span className="text-sm font-medium">{u.name}</span>
                  </div>
                  {isAdmin && (
                    <button
                      onClick={() => {
                        setShowAdd(true);
                        setNoteUserId(u.id);
                        setShowMissing(false);
                      }}
                      className="text-xs px-3 py-1 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                    >
                      Not Ekle
                    </button>
                  )}
                </div>
              ))}
            </div>
          )}
        </div>
      )}

      <div className="space-y-3">
        {loading ? (
          <p className="text-muted-foreground">Yükleniyor...</p>
        ) : notes.length === 0 ? (
          <p className="text-muted-foreground">Henüz not eklenmemiş.</p>
        ) : (
          notes.map((note) => {
            const files: UploadedFile[] = (() => {
              try { return JSON.parse(note.files); } catch { return []; }
            })();
            return (
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

                {note.jiraLink && (
                  <div className="mt-2">
                    <a
                      href={note.jiraLink}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="inline-flex items-center gap-1 text-xs text-blue-600 hover:underline"
                    >
                      <span>🔗</span>
                      {note.jiraLink.replace(/^https?:\/\//, "").replace(/\/$/, "")}
                    </a>
                  </div>
                )}

                {files.length > 0 && (
                  <div className="mt-2 space-y-1">
                    {files.map((f, i) => (
                      <a
                        key={i}
                        href={f.url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="inline-flex items-center gap-1 text-xs text-blue-600 hover:underline mr-3"
                      >
                        <span>📎</span>
                        {f.name}
                        <span className="text-muted-foreground">({formatFileSize(f.size)})</span>
                      </a>
                    ))}
                  </div>
                )}

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
            );
          })
        )}
      </div>
    </div>
  );
}
