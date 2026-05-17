<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Atur Pengeluaran Harian') }}
            </h2>
            <span class="text-sm text-gray-500 dark:text-gray-400 font-medium">
                {{ now()->translatedFormat('l, d F Y') }}
            </span>
        </div>
    </x-slot>

    <!-- Halaman utama diatur oleh Alpine.js dengan component 'expenseTracker' -->
    <div class="py-12" x-data="expenseTracker">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            
            <!-- 0. BAR KOLABORASI AKTIF (Presence Channel Status) -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 flex items-center justify-between transition-all duration-300">
                <div class="flex items-center space-x-2">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                    </span>
                    <span class="text-sm font-bold text-gray-700 dark:text-gray-300">Kolaborator Aktif Online</span>
                    <span class="text-xs text-gray-400 dark:text-gray-500" x-text="'(' + activeUsers.length + ' orang)'"></span>
                </div>
                <!-- Daftar Avatar Pengguna Aktif -->
                <div class="flex items-center -space-x-2 overflow-hidden">
                    <template x-for="user in activeUsers" :key="user.id">
                        <div class="inline-flex h-8 w-8 rounded-full ring-2 ring-white dark:ring-gray-800 bg-indigo-600 hover:bg-indigo-700 transition flex items-center justify-center text-white text-xs font-bold uppercase cursor-pointer"
                            :title="user.name"
                            x-text="user.name.substring(0, 2)">
                        </div>
                    </template>
                </div>
            </div>
            
            <!-- 1. KARTU STATISTIK RINGKASAN (Stats Cards) - Bersifat Reaktif -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Kartu Total Pengeluaran -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 transition duration-200 hover:shadow-md">
                    <p class="text-gray-500 dark:text-gray-400 text-xs font-semibold uppercase tracking-wider">Total Pengeluaran</p>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-2" x-text="formatCurrency(totalAmount)">Rp 0</h3>
                    <p class="text-gray-400 dark:text-gray-500 text-xs mt-1">Akumulasi pengeluaran keseluruhan</p>
                </div>

                <!-- Kartu Pengeluaran Hari Ini -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 transition duration-200 hover:shadow-md">
                    <p class="text-gray-500 dark:text-gray-400 text-xs font-semibold uppercase tracking-wider">Pengeluaran Hari Ini</p>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-2" x-text="formatCurrency(todayAmount)">Rp 0</h3>
                    <p class="text-gray-400 dark:text-gray-500 text-xs mt-1">Transaksi tercatat hari ini</p>
                </div>

                <!-- Kartu Pengeluaran Terbesar -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 transition duration-200 hover:shadow-md">
                    <p class="text-gray-500 dark:text-gray-400 text-xs font-semibold uppercase tracking-wider">Pengeluaran Terbesar</p>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-2" x-text="maxExpense ? formatCurrency(maxExpense.amount) : 'Rp 0'">Rp 0</h3>
                    <p class="text-gray-400 dark:text-gray-500 text-xs mt-1 truncate" x-text="maxExpense ? maxExpense.title + ' (' + maxExpense.category + ')' : 'Belum ada transaksi'"></p>
                </div>
            </div>

            <!-- 2. FORM TAMBAH PENGELUARAN BARU (Menggunakan AJAX) -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="font-bold text-lg text-gray-800 dark:text-gray-100 mb-4">
                    Tambah Pengeluaran Baru
                </h3>
                
                <form @submit.prevent="addExpense" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <!-- Input Judul -->
                    <div>
                        <label for="title" class="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">Nama Pengeluaran</label>
                        <input type="text" x-model="addForm.title" id="title" required placeholder="Contoh: Beli Makan Siang" 
                            class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500 p-3">
                    </div>

                    <!-- Input Nominal -->
                    <div>
                        <label for="amount" class="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">Jumlah Nominal (Rp)</label>
                        <input type="number" x-model="addForm.amount" id="amount" required min="0" placeholder="Nominal Rp" 
                            class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500 p-3">
                    </div>

                    <!-- Input Kategori -->
                    <div>
                        <label for="category" class="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">Kategori</label>
                        <select x-model="addForm.category" id="category" required 
                            class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500 p-3">
                            <option value="">Pilih Kategori</option>
                            <option value="Makanan">Makanan</option>
                            <option value="Transportasi">Transportasi</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>

                    <!-- Tombol Tambah -->
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm rounded-xl py-3 shadow-md hover:shadow-lg transition duration-200 flex items-center justify-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Simpan Data</span>
                    </button>
                </form>
            </div>

            <!-- 3. TABEL DAFTAR PENGELUARAN (Reaktif & Dinamis) -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="font-bold text-lg text-gray-800 dark:text-gray-100">Daftar Pengeluaran Aktif</h3>
                    <span class="text-xs text-gray-500 font-semibold" x-text="expenses.length + ' Transaksi'">
                        0 Transaksi
                    </span>
                </div>

                <!-- Tampilan Jika Data Kosong -->
                <div x-show="expenses.length === 0" class="p-12 text-center" style="display: none;">
                    <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Belum ada catatan pengeluaran harian. Mulailah dengan menambahkan data di atas!</p>
                </div>

                <!-- Tabel Data Pengeluaran -->
                <div x-show="expenses.length > 0" class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50 dark:bg-gray-900/50 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-100 dark:border-gray-700">
                                <th class="py-4 px-6">Nama Pengeluaran</th>
                                <th class="py-4 px-6 text-center">Kategori</th>
                                <th class="py-4 px-6 text-right">Nominal</th>
                                <th class="py-4 px-6 text-center">Diinput Oleh</th>
                                <th class="py-4 px-6 text-center">Tanggal Input</th>
                                <th class="py-4 px-6 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                            <!-- Loop Dinamis Menggunakan Alpine.js -->
                            <template x-for="expense in expenses" :key="expense.id">
                                <tr class="hover:bg-gray-50/30 dark:hover:bg-gray-700/20 transition duration-150">
                                    <!-- Nama Pengeluaran + Status Editing Real-time -->
                                    <td class="py-4 px-6 font-medium text-gray-800 dark:text-gray-200">
                                        <div class="flex items-center space-x-2">
                                            <span x-text="expense.title"></span>
                                            <!-- Badge Whispering ketika user lain sedang mengedit baris ini -->
                                            <template x-if="editingUsers[expense.id]">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 animate-pulse">
                                                    ⚡ sedang diedit oleh <span class="ml-1 font-bold" x-text="editingUsers[expense.id]"></span>
                                                </span>
                                            </template>
                                        </div>
                                    </td>
                                    <!-- Kategori -->
                                    <td class="py-4 px-6 text-center text-gray-800 dark:text-gray-200" x-text="expense.category"></td>
                                    <!-- Nominal -->
                                    <td class="py-4 px-6 text-right font-bold text-gray-900 dark:text-gray-100" x-text="formatCurrency(expense.amount)"></td>
                                    <!-- Pembuat -->
                                    <td class="py-4 px-6 text-center text-gray-600 dark:text-gray-400">
                                        <span class="inline-flex items-center">
                                            <span class="w-2 h-2 rounded-full bg-indigo-500 mr-2"></span>
                                            <span x-text="expense.user ? expense.user.name : (expense.user_name || 'User')"></span>
                                        </span>
                                    </td>
                                    <!-- Tanggal -->
                                    <td class="py-4 px-6 text-center text-gray-500 dark:text-gray-400 text-xs" x-text="formatDate(expense.created_at)"></td>
                                    <!-- Aksi -->
                                    <td class="py-4 px-6 text-center">
                                        <div class="flex items-center justify-center space-x-2">
                                            <!-- Tombol Edit (AJAX & modal Alpine) -->
                                            <button @click="openEdit(expense)" class="p-2 bg-yellow-50 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400 hover:bg-yellow-100 dark:hover:bg-yellow-900/50 rounded-lg transition" title="Edit Data">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </button>

                                            <!-- Tombol Riwayat Versi (History) -->
                                            <a :href="'/expenses/' + expense.id + '/history'" class="p-2 bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 hover:bg-purple-100 dark:hover:bg-purple-900/50 rounded-lg transition" title="Lihat Riwayat Perubahan">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            </a>

                                            <!-- Tombol Hapus (AJAX) -->
                                            <button @click="deleteExpense(expense.id)" class="p-2 bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/50 rounded-lg transition" title="Hapus Data">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ==================== MODAL EDIT (POWERED BY ALPINE.JS) ==================== -->
        <div class="fixed inset-0 z-50 overflow-y-auto" x-show="openEditModal" style="display: none;" x-transition>
            <!-- Background Backdrop -->
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="closeEdit()"></div>

            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-xl border border-gray-100 dark:border-gray-700 transform transition-all"
                    x-show="openEditModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                    
                    <!-- Header Modal -->
                    <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3 mb-4">
                        <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 flex items-center">
                            <span class="p-1.5 bg-yellow-50 dark:bg-yellow-900/30 rounded-lg text-yellow-600 mr-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </span>
                            Edit Pengeluaran
                        </h3>
                        <button @click="closeEdit()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Form Edit (AJAX) -->
                    <form @submit.prevent="updateExpense(false)" class="space-y-4">
                        <!-- Edit Judul -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">Nama Pengeluaran</label>
                            <input type="text" required x-model="editForm.title" 
                                class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500 p-3">
                        </div>

                        <!-- Edit Nominal -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">Jumlah Nominal (Rp)</label>
                            <input type="number" required min="0" x-model="editForm.amount" 
                                class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500 p-3">
                        </div>

                        <!-- Edit Kategori -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">Kategori</label>
                            <select required x-model="editForm.category" 
                                class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500 p-3">
                                <option value="Makanan">Makanan</option>
                                <option value="Transportasi">Transportasi</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>

                        <!-- Tombol Submit & Cancel -->
                        <div class="flex space-x-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                            <button type="button" @click="closeEdit()" 
                                class="flex-1 px-4 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-semibold text-sm rounded-xl transition duration-155 text-center">
                                Batal
                            </button>
                            <button type="submit" 
                                class="flex-1 px-4 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm rounded-xl transition duration-155 shadow-md hover:shadow-lg text-center">
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ==================== DIALOG RESOLUSI KONFLIK (INTERACTIVE RESOLUTION) ==================== -->
        <div class="fixed inset-0 z-50 overflow-y-auto" x-show="conflict.show" style="display: none;" x-transition>
            <!-- Background Backdrop -->
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity"></div>

            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-2xl border-2 border-red-500 transform transition-all"
                    x-show="conflict.show" x-transition>
                    
                    <!-- Header Modal -->
                    <div class="flex items-center space-x-3 border-b border-gray-100 dark:border-gray-700 pb-3 mb-4">
                        <span class="p-2 bg-red-100 dark:bg-red-900/30 rounded-xl text-red-600 dark:text-red-400 animate-bounce">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </span>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Bentrokan Perubahan Data (Conflict Detected)</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Pengguna lain telah merubah data ini saat Anda sedang melakukan proses edit.</p>
                        </div>
                    </div>

                    <!-- Perbandingan Data -->
                    <div class="space-y-4 mb-6">
                        <p class="text-sm text-gray-700 dark:text-gray-300" x-text="conflict.message"></p>
                        
                        <div class="grid grid-cols-2 gap-4 bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl text-xs border border-gray-100 dark:border-gray-800">
                            <div>
                                <p class="font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Input Anda (Draft Anda)</p>
                                <ul class="space-y-1 text-gray-800 dark:text-gray-200">
                                    <li><strong>Nama:</strong> <span x-text="editForm.title"></span></li>
                                    <li><strong>Nominal:</strong> <span x-text="formatCurrency(editForm.amount)"></span></li>
                                    <li><strong>Kategori:</strong> <span x-text="editForm.category"></span></li>
                                </ul>
                            </div>
                            <div class="border-l border-gray-200 dark:border-gray-700 pl-4">
                                <p class="font-bold text-red-500 uppercase tracking-wider mb-2">Data di Server Saat Ini</p>
                                <template x-if="conflict.currentData">
                                    <ul class="space-y-1 text-gray-800 dark:text-gray-200">
                                        <li><strong>Nama:</strong> <span x-text="conflict.currentData.title"></span></li>
                                        <li><strong>Nominal:</strong> <span x-text="formatCurrency(conflict.currentData.amount)"></span></li>
                                        <li><strong>Kategori:</strong> <span x-text="conflict.currentData.category"></span></li>
                                        <li><strong>Oleh:</strong> <span class="font-bold text-indigo-500" x-text="conflict.currentData.user ? conflict.currentData.user.name : 'Kolaborator'"></span></li>
                                    </ul>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Pilihan Resolusi -->
                    <div class="flex space-x-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <button type="button" @click="resolveConflictKeepTheirs" 
                            class="flex-1 px-4 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-semibold text-sm rounded-xl transition duration-155 text-center">
                            Batal & Pakai Data Server
                        </button>
                        <button type="button" @click="resolveConflictKeepMine" 
                            class="flex-1 px-4 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold text-sm rounded-xl transition duration-155 shadow-md hover:shadow-lg text-center">
                            Tetap Simpan (Timpa!)
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== FLOATING NOTIFICATION POPUP (SUCCESS ALERT) ==================== -->
        <div x-data="{ show: false, message: '', type: 'success' }" 
            x-show="show" 
            x-on:alert.window="show = true; message = $event.detail.message; type = $event.detail.type; setTimeout(() => show = false, 4000)" 
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-2"
            class="fixed bottom-5 right-5 z-50 flex items-center p-4 text-sm rounded-2xl shadow-xl max-w-sm border"
            :class="type === 'success' ? 'text-green-800 bg-green-50 border-green-200 dark:bg-gray-900 dark:text-green-400 dark:border-green-800' : (type === 'warning' ? 'text-yellow-800 bg-yellow-50 border-yellow-200 dark:bg-gray-900 dark:text-yellow-400 dark:border-yellow-800' : 'text-red-800 bg-red-50 border-red-200 dark:bg-gray-900 dark:text-red-400 dark:border-red-800')"
            style="display: none;" role="alert">
            
            <!-- Icon Dynamic -->
            <template x-if="type === 'success'">
                <svg class="flex-shrink-0 inline w-5 h-5 mr-3 text-green-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
            </template>
            <template x-if="type === 'warning'">
                <svg class="flex-shrink-0 inline w-5 h-5 mr-3 text-yellow-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
            </template>
            <template x-if="type === 'error'">
                <svg class="flex-shrink-0 inline w-5 h-5 mr-3 text-red-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
            </template>

            <div>
                <span class="font-bold" x-text="type === 'success' ? 'Berhasil!' : (type === 'warning' ? 'Perhatian!' : 'Error!')"></span> 
                <span x-text="message"></span>
            </div>
            
            <button @click="show = false" class="ml-auto -mx-1.5 -my-1.5 rounded-lg p-1.5 inline-flex h-8 w-8 items-center justify-center"
                :class="type === 'success' ? 'text-green-500 hover:bg-green-100 dark:hover:bg-gray-800' : (type === 'warning' ? 'text-yellow-500 hover:bg-yellow-100 dark:hover:bg-gray-800' : 'text-red-500 hover:bg-red-100 dark:hover:bg-gray-800')">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    <!-- Alpine.js Component Logic Script -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('expenseTracker', () => ({
                // Inisialisasi awal list dari data database Blade
                expenses: @json($expenses),
                
                // Form States
                addForm: {
                    title: '',
                    amount: '',
                    category: ''
                },
                
                editForm: {
                    id: '',
                    title: '',
                    amount: '',
                    category: '',
                    updated_at: ''
                },
                
                openEditModal: false,
                
                // Real-time Collaboration States
                activeUsers: [],
                editingUsers: {}, // { expenseId: userName }
                
                // Konflik Resolusi State
                conflict: {
                    show: false,
                    message: '',
                    currentData: null
                },

                init() {
                    // Cek ketersediaan Laravel Echo
                    if (window.Echo) {
                        // 1. Join Presence Channel 'dashboard' untuk memantau kolaborator aktif
                        window.Echo.join('dashboard')
                            .here((users) => {
                                this.activeUsers = users;
                            })
                            .joining((user) => {
                                this.activeUsers.push(user);
                                this.triggerNotification('success', user.name + ' bergabung kolaborasi.');
                            })
                            .leaving((user) => {
                                this.activeUsers = this.activeUsers.filter(u => u.id !== user.id);
                                this.triggerNotification('warning', user.name + ' meninggalkan kolaborasi.');
                            })
                            
                            // 2. Dengarkan Whispering untuk tahu siapa yang sedang edit baris mana
                            .listenForWhisper('editing', (e) => {
                                this.editingUsers[e.expenseId] = e.userName;
                            })
                            .listenForWhisper('stopped-editing', (e) => {
                                delete this.editingUsers[e.expenseId];
                            })
                            
                            // 3. Dengarkan Event Real-Time Broadcasting
                            .listen('ExpenseCreated', (e) => {
                                // Masukkan data baru ke list paling atas (tanpa refresh)
                                this.expenses.unshift(e.expense);
                                this.triggerNotification('success', 'Pengeluaran "' + e.expense.title + '" baru saja ditambahkan oleh ' + e.expense.user.name);
                            })
                            .listen('ExpenseUpdated', (e) => {
                                // Ganti item lama di dalam array dengan yang ter-update
                                const index = this.expenses.findIndex(item => item.id === e.expense.id);
                                if (index !== -1) {
                                    this.expenses[index] = e.expense;
                                }
                                this.triggerNotification('success', 'Pengeluaran "' + e.expense.title + '" diperbarui oleh ' + e.expense.user.name);
                            })
                            .listen('ExpenseDeleted', (e) => {
                                // Hapus data dari daftar di browser
                                const deletedItem = this.expenses.find(item => item.id === e.expenseId);
                                const title = deletedItem ? '"' + deletedItem.title + '"' : 'suatu data';
                                
                                this.expenses = this.expenses.filter(item => item.id !== e.expenseId);
                                this.triggerNotification('warning', 'Pengeluaran ' + title + ' telah dihapus oleh pengguna lain.');
                            });
                    }
                },

                // Card Stats Computed Properties (Reaktif)
                get totalAmount() {
                    return this.expenses.reduce((sum, e) => sum + Number(e.amount), 0);
                },

                get todayAmount() {
                    const today = new Date();
                    const todayStr = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0');
                    return this.expenses
                        .filter(e => {
                            if (!e.created_at) return false;
                            return e.created_at.substring(0, 10) === todayStr;
                        })
                        .reduce((sum, e) => sum + Number(e.amount), 0);
                },

                get maxExpense() {
                    if (this.expenses.length === 0) return null;
                    return this.expenses.reduce((max, e) => Number(e.amount) > Number(max.amount) ? e : max, this.expenses[0]);
                },

                // Format Nominal Rupiah
                formatCurrency(value) {
                    return 'Rp ' + Number(value).toLocaleString('id-ID');
                },

                // Format Tanggal ke lokal Indonesia
                formatDate(dateStr) {
                    if (!dateStr) return '-';
                    const date = new Date(dateStr);
                    return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) + ', ' + 
                           date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                },

                // 1. TAMBAH DATA (AJAX - Axios)
                async addExpense() {
                    try {
                        const response = await axios.post('/expenses', this.addForm);
                        if (response.data.success) {
                            // Tambahkan ke array lokal agar langsung ter-render di layar sendiri
                            this.expenses.unshift(response.data.expense);
                            
                            // Reset input form
                            this.addForm.title = '';
                            this.addForm.amount = '';
                            this.addForm.category = '';
                            
                            this.triggerNotification('success', response.data.message);
                        }
                    } catch (error) {
                        this.triggerNotification('error', error.response?.data?.message || 'Terjadi kesalahan sistem.');
                    }
                },

                // 2. EDIT & UPDATE DATA (AJAX - Axios)
                openEdit(expense) {
                    this.editForm.id = expense.id;
                    this.editForm.title = expense.title;
                    this.editForm.amount = expense.amount;
                    this.editForm.category = expense.category;
                    this.editForm.updated_at = expense.updated_at; // Simpan timestamp terakhir untuk deteksi konflik
                    this.openEditModal = true;

                    // Kirim sinyal (Whisper) bahwa kita sedang mengedit baris ini ke pengguna lain
                    if (window.Echo) {
                        window.Echo.join('dashboard').whisper('editing', {
                            expenseId: expense.id,
                            userName: '{{ Auth::user()->name }}'
                        });
                    }
                },

                closeEdit() {
                    // Beri tahu pengguna lain bahwa kita berhenti mengedit baris ini
                    if (window.Echo) {
                        window.Echo.join('dashboard').whisper('stopped-editing', {
                            expenseId: this.editForm.id
                        });
                    }
                    this.openEditModal = false;
                },

                async updateExpense(force = false) {
                    try {
                        const payload = {
                            title: this.editForm.title,
                            amount: this.editForm.amount,
                            category: this.editForm.category,
                            last_known_updated_at: this.editForm.updated_at,
                            force: force
                        };

                        const response = await axios.patch('/expenses/' + this.editForm.id, payload);
                        
                        if (response.data.success) {
                            // Update array pengeluaran di browser sendiri
                            const index = this.expenses.findIndex(item => item.id === response.data.expense.id);
                            if (index !== -1) {
                                this.expenses[index] = response.data.expense;
                            }
                            
                            // Tutup modal & bersihkan state konflik
                            this.closeEdit();
                            this.conflict.show = false;
                            
                            this.triggerNotification('success', response.data.message);
                        }
                    } catch (error) {
                        // Jika server mengembalikan 409 Conflict
                        if (error.response && error.response.status === 409) {
                            this.conflict.message = error.response.data.message;
                            this.conflict.currentData = error.response.data.current_data;
                            this.conflict.show = true;
                        } else {
                            this.triggerNotification('error', error.response?.data?.message || 'Gagal menyimpan perubahan.');
                        }
                    }
                },

                // Resolusi Konflik: Pilihan User
                resolveConflictKeepMine() {
                    // Lakukan update paksa dengan parameter 'force=true' (Last Write Wins)
                    this.updateExpense(true);
                },

                resolveConflictKeepTheirs() {
                    // Ambil nilai terbaru di server dan terapkan di browser
                    const serverData = this.conflict.currentData;
                    const index = this.expenses.findIndex(item => item.id === serverData.id);
                    if (index !== -1) {
                        this.expenses[index] = serverData;
                    }
                    
                    // Tutup modal konflik & batal edit
                    this.conflict.show = false;
                    this.closeEdit();
                    this.triggerNotification('warning', 'Pembaruan dibatalkan. Anda memilih menggunakan data terbaru di server.');
                },

                // 3. HAPUS DATA (AJAX - Axios)
                async deleteExpense(expenseId) {
                    if (!confirm('Apakah Anda yakin ingin menghapus data pengeluaran ini?')) return;
                    
                    try {
                        const response = await axios.delete('/expenses/' + expenseId);
                        if (response.data.success) {
                            // Hapus dari list di browser sendiri
                            this.expenses = this.expenses.filter(item => item.id !== expenseId);
                            this.triggerNotification('success', response.data.message);
                        }
                    } catch (error) {
                        this.triggerNotification('error', error.response?.data?.message || 'Gagal menghapus data.');
                    }
                },

                // Trigger popup notifikasi
                triggerNotification(type, message) {
                    window.dispatchEvent(new CustomEvent('alert', { 
                        detail: { type: type, message: message } 
                    }));
                }
            }));
        });
    </script>
</x-app-layout>
