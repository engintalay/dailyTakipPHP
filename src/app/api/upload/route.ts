import { requireAuth } from "@/lib/auth-helpers";
import { NextResponse } from "next/server";
import { writeFile, mkdir } from "fs/promises";
import path from "path";

export async function POST(req: Request) {
  await requireAuth();

  const formData = await req.formData();
  const file = formData.get("file") as File | null;

  if (!file) {
    return NextResponse.json({ error: "Dosya gerekli" }, { status: 400 });
  }

  const uploadsDir = path.join(process.cwd(), "public", "uploads");
  await mkdir(uploadsDir, { recursive: true });

  const ext = path.extname(file.name);
  const safeName = `${Date.now()}-${Math.random().toString(36).slice(2, 8)}${ext}`;
  const buffer = Buffer.from(await file.arrayBuffer());
  const filePath = path.join(uploadsDir, safeName);
  await writeFile(filePath, buffer);

  return NextResponse.json({
    name: file.name,
    url: `/uploads/${safeName}`,
    size: file.size,
    type: file.type,
  });
}
