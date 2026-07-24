import { PrismaClient } from "@prisma/client";
import { hash } from "bcryptjs";

const prisma = new PrismaClient();

async function main() {
  const adminPassword = await hash("admin123", 12);
  const userPassword = await hash("user123", 12);

  const admin = await prisma.user.upsert({
    where: { email: "admin@dailytakip.com" },
    update: {},
    create: {
      name: "Admin",
      email: "admin@dailytakip.com",
      passwordHash: adminPassword,
      role: "ADMIN",
    },
  });

  const user1 = await prisma.user.upsert({
    where: { email: "ali@dailytakip.com" },
    update: {},
    create: {
      name: "Ali Yılmaz",
      email: "ali@dailytakip.com",
      passwordHash: userPassword,
      role: "MEMBER",
    },
  });

  const user2 = await prisma.user.upsert({
    where: { email: "ayse@dailytakip.com" },
    update: {},
    create: {
      name: "Ayşe Demir",
      email: "ayse@dailytakip.com",
      passwordHash: userPassword,
      role: "MEMBER",
    },
  });

  console.log("Seed verileri eklendi:");
  console.log(`  Admin: admin@dailytakip.com / admin123`);
  console.log(`  Ali:   ali@dailytakip.com   / user123`);
  console.log(`  Ayşe:  ayse@dailytakip.com  / user123`);
}

main()
  .catch((e) => {
    console.error(e);
    process.exit(1);
  })
  .finally(async () => {
    await prisma.$disconnect();
  });
