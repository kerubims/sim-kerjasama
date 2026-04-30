{{-- Send to Client Modal --}}
<div x-show="showSendModal" class="fixed inset-0 bg-slate-900/50 flex items-center justify-center z-50" style="display:none;">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md" @click.outside="showSendModal=false">
        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                    <i class="fa-solid fa-paper-plane text-blue-600 text-xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold leading-6 text-slate-900">Kirim Draft ke Client</h3>
                    <p class="text-sm text-slate-500">Dokumen akan dikirim untuk direview</p>
                </div>
            </div>
            <div class="bg-slate-50 rounded-lg p-4 mb-4">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="font-medium text-slate-800">Izinkan Client Upload Draft</div>
                        <p class="text-xs text-slate-500 mt-1">Jika diaktifkan, client dapat mengupload versi draft kerjasama mereka sendiri</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="allowUpload" class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
            </div>
            <div x-show="allowUpload" class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                <div class="flex items-start gap-2">
                    <i class="fa-solid fa-info-circle text-blue-600 mt-0.5"></i>
                    <div class="text-sm text-blue-800">
                        <strong>Catatan:</strong> Client akan dapat mengupload file dokumen (.doc, .docx, .pdf) sebagai versi alternatif dari draft ini.
                    </div>
                </div>
            </div>
            <div class="text-sm text-slate-600">
                <i class="fa-solid fa-clock text-slate-400 mr-1"></i>
                Client akan menerima notifikasi untuk mereview dokumen ini.
            </div>
        </div>
        <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 rounded-b-lg">
            <button @click="confirmSendToClient()" class="inline-flex w-full justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 sm:ml-3 sm:w-auto">
                <i class="fa-solid fa-paper-plane mr-2"></i> Kirim ke Client
            </button>
            <button @click="showSendModal=false" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto">Batal</button>
        </div>
    </div>
</div>

{{-- Sign Modal --}}
<div x-show="showSignModal" class="fixed inset-0 bg-slate-900/50 flex items-center justify-center z-50" style="display:none;">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md" @click.outside="showSignModal=false">
        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                    <i class="fa-solid fa-signature text-green-600 text-xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold leading-6 text-slate-900">Upload Tanda Tangan</h3>
                    <p class="text-sm text-slate-500">Upload gambar tanda tangan Anda</p>
                </div>
            </div>
            <div id="signature-upload-area" class="border-2 border-dashed border-slate-300 rounded-lg bg-slate-50 p-6 text-center cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition" @click="$refs.sigFile.click()" @dragover.prevent="$event.target.closest('#signature-upload-area').classList.add('border-blue-500','bg-blue-50')" @dragleave.prevent="$event.target.closest('#signature-upload-area').classList.remove('border-blue-500','bg-blue-50')" @drop.prevent="handleSignatureDrop($event)">
                <input type="file" x-ref="sigFile" accept="image/*" class="hidden" @change="previewSignature($event)">
                <div x-show="!signaturePreview">
                    <i class="fa-solid fa-cloud-arrow-up text-4xl text-slate-400 mb-3"></i>
                    <p class="text-sm font-medium text-slate-700">Klik atau drag & drop untuk upload</p>
                    <p class="text-xs text-slate-500 mt-1">PNG, JPG, atau GIF (Maks. 2MB)</p>
                </div>
                <div x-show="signaturePreview">
                    <img :src="signaturePreview" class="max-h-32 mx-auto mb-3 border border-slate-200 rounded bg-white p-2">
                    <button type="button" @click.stop="$refs.sigFile.click()" class="text-sm text-blue-600 hover:underline">
                        <i class="fa-solid fa-refresh mr-1"></i> Ganti Gambar
                    </button>
                </div>
            </div>
            <div class="mt-4 bg-amber-50 border border-amber-200 rounded-lg p-3">
                <div class="flex items-start gap-2">
                    <i class="fa-solid fa-lightbulb text-amber-600 mt-0.5"></i>
                    <div class="text-xs text-amber-800">
                        <strong>Tips:</strong> Untuk hasil terbaik, gunakan gambar tanda tangan dengan latar belakang transparan (PNG) atau putih.
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 rounded-b-lg">
            <button @click="submitSignature()" :disabled="!signaturePreview" :class="signaturePreview ? 'bg-green-600 hover:bg-green-500' : 'bg-slate-300 cursor-not-allowed'" class="inline-flex w-full justify-center rounded-md px-4 py-2 text-sm font-semibold text-white shadow-sm sm:ml-3 sm:w-auto">
                <i class="fa-solid fa-check mr-2"></i> Simpan Tanda Tangan
            </button>
            <button @click="showSignModal=false;signaturePreview=null" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto">Batal</button>
        </div>
    </div>
</div>
