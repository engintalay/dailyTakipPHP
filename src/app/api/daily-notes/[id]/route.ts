import { prisma } from "@/lib/prisma";
import { requireAuth, getSessionUser } from "@/lib/auth-helpers";
import { NextResponse } from "next/server";

export async function PATCH(req: Request, { params }: { params: Promise<{ id: string }> }) {
  const user = await requireAuth();
  const { id } = await params;
  const body = await req.json();

  const note = await prisma.dailyNote.findUnique({ where: { id } });
  if (!note) return NextResponse.json({ error: "Not bulunamadı" }, { status: 404 });

  if (note.userId !== user.id && user.role !== "ADMIN") {
    return NextResponse.json({ error: "Unauthorized" }, { status: 403 });
  }

  const updated = await prisma.dailyNote.update({
    where: { id },
    data: {
      ...(body.content && { content: body.content }),
      ...(body.tags !== undefined && { tags: body.tags }),
      ...(body.date && { date: new Date(body.date) }),
      ...(body.jiraLink !== undefined && { jiraLink: body.jiraLink }),
      ...(body.files !== undefined && { files: body.files }),
    },
    include: {
      user: { select: { id: true, name: true, email: true } },
    },
  });

  return NextResponse.json(updated);
}

export async function DELETE(req: Request, { params }: { params: Promise<{ id: string }> }) {
  const user = await requireAuth();
  const { id } = await params;

  const note = await prisma.dailyNote.findUnique({ where: { id } });
  if (!note) return NextResponse.json({ error: "Not bulunamadı" }, { status: 404 });

  if (note.userId !== user.id && user.role !== "ADMIN") {
    return NextResponse.json({ error: "Unauthorized" }, { status: 403 });
  }

  await prisma.dailyNote.delete({ where: { id } });
  return NextResponse.json({ success: true });
}
