export default function Home() {

  return (

    <div>

      <h1 className="text-3xl font-bold mb-6">Selamat Datang di Sistem Manajemen KSP Lam Gabe Jaya</h1>

      <p className="mb-4">Kelola anggota, pinjaman, dan formulir dengan mudah.</p>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">

        <div className="bg-white p-6 rounded shadow">

          <h2 className="text-xl font-semibold mb-2">Anggota</h2>

          <p>Kelola data anggota koperasi.</p>

          <a href="/members" className="text-blue-500 mt-2 inline-block">Lihat Anggota</a>

        </div>

        <div className="bg-white p-6 rounded shadow">

          <h2 className="text-xl font-semibold mb-2">Pinjaman</h2>

          <p>Kelola aplikasi dan pembayaran pinjaman.</p>

          <a href="/loans" className="text-blue-500 mt-2 inline-block">Lihat Pinjaman</a>

        </div>

        <div className="bg-white p-6 rounded shadow">

          <h2 className="text-xl font-semibold mb-2">Formulir</h2>

          <p>Buat surat permohonan dan kesepakatan.</p>

          <a href="/forms" className="text-blue-500 mt-2 inline-block">Buat Formulir</a>

        </div>

      </div>

    </div>

  )

}
