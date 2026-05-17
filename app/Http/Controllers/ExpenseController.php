<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Expense;
use App\Models\ExpenseVersion;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    /**
     * Menampilkan Dashboard utama yang berisi daftar pengeluaran harian,
     * form untuk menambah pengeluaran baru, serta kartu statistik ringkasan.
     */
    public function index()
    {
        // 1. Mengambil seluruh data pengeluaran beserta relasi 'user' yang membuatnya
        // Diurutkan dari yang paling baru diinput (created_at desc)
        $expenses = Expense::with('user')->orderBy('created_at', 'desc')->get();

        // 2. Menghitung ringkasan statistik untuk kartu informasi (stats cards)
        $totalAmount = Expense::sum('amount'); // Total nominal semua pengeluaran
        $todayAmount = Expense::whereDate('created_at', today())->sum('amount'); // Pengeluaran hari ini
        $maxExpense = Expense::orderBy('amount', 'desc')->first(); // Pengeluaran dengan nominal terbesar

        // 3. Mengirimkan data tersebut ke tampilan Blade 'dashboard'
        return view('dashboard', compact('expenses', 'totalAmount', 'todayAmount', 'maxExpense'));
    }

    /**
     * Menyimpan data pengeluaran baru ke database,
     * sekaligus mencatat versi pertama (versi 1) ke tabel expense_versions untuk history.
     * Mendukung respons JSON untuk AJAX dan memicu event Broadcasting ke user lain.
     */
    public function store(Request $request)
    {
        // 1. Validasi input dari form
        $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'category' => 'required|in:Makanan,Transportasi,Lainnya',
        ]);

        // 2. Simpan data pengeluaran utama ke tabel 'expenses'
        $expense = Expense::create([
            'user_id' => Auth::id(),
            'title' => $request->title,
            'amount' => $request->amount,
            'category' => $request->category,
        ]);

        // 3. Secara otomatis, buat log versi pertama (Versi Awal) ke tabel 'expense_versions'
        ExpenseVersion::create([
            'expense_id' => $expense->id,
            'title' => $expense->title,
            'amount' => $expense->amount,
            'category' => $expense->category,
            'updated_by' => Auth::id(),
            'created_at' => $expense->created_at,
        ]);

        // Muat relasi user agar nama penginput langsung tersedia di frontend
        $expense->load('user');

        // 4. Siarkan event ke pengguna lain secara real-time melalui Reverb
        broadcast(new \App\Events\ExpenseCreated($expense))->toOthers();

        // 5. Jika request meminta JSON (AJAX), kembalikan data baru
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pengeluaran berhasil ditambahkan.',
                'expense' => $expense,
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Pengeluaran berhasil ditambahkan.');
    }

    /**
     * Memperbarui data pengeluaran yang sudah ada,
     * sekaligus mencatat log perubahan (versi baru) ke tabel expense_versions.
     * Menggunakan resolusi konflik sederhana "Last Write Wins".
     */
    public function update(Request $request, Expense $expense)
    {
        // 1. Validasi input dari form edit
        $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'category' => 'required|in:Makanan,Transportasi,Lainnya',
        ]);

        // 2. Deteksi Konflik (Conflict Resolution)
        // Kita periksa apakah ada user lain yang telah melakukan update terlebih dahulu
        if ($request->filled('last_known_updated_at') && !$request->boolean('force')) {
            $clientTime = \Carbon\Carbon::parse($request->last_known_updated_at);
            
            // Bandingkan timestamp database dengan timestamp terakhir yang diketahui client
            if ($expense->updated_at->timestamp > $clientTime->timestamp) {
                return response()->json([
                    'conflict' => true,
                    'message' => 'Konflik terdeteksi! Data ini baru saja diperbarui oleh pengguna lain.',
                    'current_data' => $expense->load('user'),
                ], 409); // Status 409: Conflict
            }
        }

        // 3. Perbarui data pengeluaran utama di database
        $expense->update([
            'title' => $request->title,
            'amount' => $request->amount,
            'category' => $request->category,
        ]);

        // 4. Buat log versi baru (Versi Perubahan) di tabel 'expense_versions'
        ExpenseVersion::create([
            'expense_id' => $expense->id,
            'title' => $expense->title,
            'amount' => $expense->amount,
            'category' => $expense->category,
            'updated_by' => Auth::id(),
        ]);

        // Muat relasi user
        $expense->load('user');

        // 5. Siarkan perubahan ke pengguna aktif lain secara real-time
        broadcast(new \App\Events\ExpenseUpdated($expense))->toOthers();

        // 6. Jika request meminta JSON (AJAX)
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pengeluaran berhasil diperbarui.',
                'expense' => $expense,
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Pengeluaran berhasil diperbarui.');
    }

    /**
     * Menghapus data pengeluaran dari database.
     * Karena relasi database di migrasi diatur ->cascadeOnDelete(),
     * maka seluruh data versi history di tabel 'expense_versions' akan terhapus otomatis.
     */
    public function destroy(Expense $expense)
    {
        $expenseId = $expense->id;

        // Hapus data dari tabel 'expenses'
        $expense->delete();

        // Siarkan penghapusan ke pengguna aktif lain
        broadcast(new \App\Events\ExpenseDeleted($expenseId))->toOthers();

        // Jika request meminta JSON (AJAX)
        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pengeluaran berhasil dihapus.',
                'expense_id' => $expenseId,
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Pengeluaran berhasil dihapus.');
    }

    /**
     * Menampilkan riwayat perubahan (Version History) dari satu pengeluaran tertentu.
     */
    public function history(Expense $expense)
    {
        // 1. Mengambil seluruh riwayat versi pengeluaran ini beserta relasi 'updater' (siapa yang mengubah)
        // Diurutkan dari perubahan paling baru ke lama
        $versions = $expense->versions()->with('updater')->orderBy('created_at', 'desc')->get();

        // 2. Tampilkan view 'expenses.history' (halaman timeline history)
        return view('expenses.history', compact('expense', 'versions'));
    }
}
