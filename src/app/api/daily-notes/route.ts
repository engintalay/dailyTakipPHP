import { prisma } from "@/lib/prisma";
import { getEffectiveUserId, requireAuth } from "@/lib/auth-helpers";
import { NextResponse } from "next/server";

export async function GET(req: Request) {
  await requireAuth();

  const url = new URL(req.url);
  const userId = url.searchParams.get("userId");
  const startDate = url.searchParams.get("startDate");
  const endDate = url.searchParams.get("endDate");
  const search = url.searchParams.get("search");
  const tag = url.searchParams.get("tag");

  const where: any = {};

  if (userId) where.userId = userId;
  if (search) where.content = { contains: search };
  if (tag) where.tags = { contains: tag };

  if (startDate || endDate) {
    where.date = {};
    if (startDate) where.date.gte = new Date(startDate);
    if (endDate) where.date.lte = new Date(endDate + "T23:59:59.999Z");
  }

  const notes = await prisma.dailyNote.findMany({
    where,
    orderBy: { date: "desc" },
    include: {
      user: { select: { id: true, name: true, email: true } },
    },
  });

  return NextResponse.json(notes);
}

export async function POST(req: Request) {
  const userId = await getEffectiveUserId();
  if (!userId) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const body = await req.json();
  const { date, content, tags } = body;

  if (!date || !content) {
    return NextResponse.json({ error: "Tarih ve içerik gerekli" }, { status: 400 });
  }

  const note = await prisma.dailyNote.create({
    data: {
      userId,
      date: new Date(date),
      content,
      tags: tags || "",
    },
    include: {
      user: { select: { id: true, name: true, email: true } },
    },
  });

  return NextResponse.json(note);
}
