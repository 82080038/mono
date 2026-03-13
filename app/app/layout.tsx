import Link from 'next/link'

export default function RootLayout({

  children,

}: {

  children: React.ReactNode

}) {

  return (

    <html lang="id">

      <body className="bg-gray-100">

        <nav className="bg-white shadow p-4">

          <div className="container mx-auto flex justify-between">

            <Link href="/" className="text-xl font-bold">KSP Lam Gabe Jaya</Link>

            <div className="space-x-4">

              <Link href="/members">Anggota</Link>

              <Link href="/loans">Pinjaman</Link>

              <Link href="/forms">Formulir</Link>

            </div>

          </div>

        </nav>

        <main className="container mx-auto p-4">

          {children}

        </main>

      </body>

    </html>

  )

}
