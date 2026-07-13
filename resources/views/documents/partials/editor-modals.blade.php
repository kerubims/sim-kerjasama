{{-- Send to Unit Pengusul Modal --}}
<div x-show="showSendModal" class="fixed inset-0 bg-slate-900/50 flex items-center justify-center z-50" style="display:none;">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md" @click.outside="showSendModal=false">
        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                    <i class="fa-solid fa-paper-plane text-blue-600 text-xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold leading-6 text-slate-900">Kirim Draft ke Unit Pengusul</h3>
                    <p class="text-sm text-slate-500">Dokumen akan dikirim untuk direview dan ditandatangani</p>
                </div>
            </div>
            <div class="text-sm text-slate-600">
                <i class="fa-solid fa-info-circle text-blue-500 mr-1"></i>
                Alur persetujuan: <strong>Unit Pengusul</strong> review & tanda tangan terlebih dahulu, kemudian diteruskan ke <strong>Mitra</strong>.
            </div>
        </div>
        <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 rounded-b-lg">
            <button @click="confirmSendToUnit()" class="inline-flex w-full justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 sm:ml-3 sm:w-auto">
                <i class="fa-solid fa-paper-plane mr-2"></i> Kirim ke Unit Pengusul
            </button>
            <button @click="showSendModal=false" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto">Batal</button>
        </div>
    </div>
</div>

{{-- Sign Modal (Tanda Tangan + Stempel) --}}
<div x-show="showSignModal" class="fixed inset-0 bg-slate-900/50 flex items-center justify-center z-50" style="display:none;">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto" @click.outside="showSignModal=false">
        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                    <i class="fa-solid fa-signature text-green-600 text-xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold leading-6 text-slate-900">Upload Tanda Tangan & Stempel</h3>
                    <p class="text-sm text-slate-500">Upload gambar tanda tangan dan stempel keabsahan</p>
                </div>
            </div>

            {{-- Tanda Tangan --}}
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-2"><i class="fa-solid fa-pen-nib mr-1 text-slate-400"></i> Tanda Tangan <span class="text-red-500">*</span></label>
                <div id="signature-upload-area" class="border-2 border-dashed border-slate-300 rounded-lg bg-slate-50 p-4 text-center cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition" @click="$refs.sigFile.click()" @dragover.prevent @drop.prevent="handleSignatureDrop($event)">
                    <input type="file" x-ref="sigFile" accept="image/*" class="hidden" @change="previewSignature($event)">
                    <div x-show="!signaturePreview">
                        <i class="fa-solid fa-cloud-arrow-up text-3xl text-slate-400 mb-2"></i>
                        <p class="text-sm font-medium text-slate-700">Klik atau drag & drop untuk upload</p>
                        <p class="text-xs text-slate-500 mt-1">PNG, JPG (Maks. 2MB)</p>
                    </div>
                    <div x-show="signaturePreview">
                        <img :src="signaturePreview" class="max-h-24 mx-auto mb-2 border border-slate-200 rounded bg-white p-1">
                        <button type="button" @click.stop="$refs.sigFile.click()" class="text-xs text-blue-600 hover:underline">
                            <i class="fa-solid fa-refresh mr-1"></i> Ganti Gambar
                        </button>
                    </div>
                </div>
            </div>

            {{-- Stempel Keabsahan --}}
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-2"><i class="fa-solid fa-stamp mr-1 text-slate-400"></i> Stempel Keabsahan <span class="text-xs text-slate-400 font-normal">(opsional)</span></label>
                <div class="border-2 border-dashed border-slate-300 rounded-lg bg-slate-50 p-4 text-center cursor-pointer hover:border-green-400 hover:bg-green-50 transition" @click="$refs.stampFile.click()" @dragover.prevent @drop.prevent="handleStampDrop($event)">
                    <input type="file" x-ref="stampFile" accept="image/*" class="hidden" @change="previewStamp($event)">
                    <div x-show="!stampPreview">
                        <i class="fa-solid fa-stamp text-3xl text-slate-400 mb-2"></i>
                        <p class="text-sm font-medium text-slate-700">Upload stempel keabsahan</p>
                        <p class="text-xs text-slate-500 mt-1">PNG, JPG (Maks. 2MB) — akan menindih tanda tangan</p>
                    </div>
                    <div x-show="stampPreview">
                        <img :src="stampPreview" class="max-h-24 mx-auto mb-2 border border-slate-200 rounded bg-white p-1">
                        <button type="button" @click.stop="$refs.stampFile.click()" class="text-xs text-green-600 hover:underline">
                            <i class="fa-solid fa-refresh mr-1"></i> Ganti Stempel
                        </button>
                    </div>
                </div>
            </div>

            {{-- Preview overlay --}}
            <div x-show="signaturePreview && stampPreview" class="bg-slate-50 border border-slate-200 rounded-lg p-3 mb-4">
                <div class="text-xs font-medium text-slate-600 mb-2"><i class="fa-solid fa-eye mr-1"></i> Preview Hasil:</div>
                <div class="relative w-full text-center flex justify-center items-center h-24">
                    <img :src="signaturePreview" class="relative max-h-20" style="z-index: 10;">
                    <img :src="stampPreview" class="absolute h-24 w-24 object-contain" style="z-index: 50; margin-left: 40px; margin-top: 10px;">
                </div>
            </div>

            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
                <div class="flex items-start gap-2">
                    <i class="fa-solid fa-lightbulb text-amber-600 mt-0.5"></i>
                    <div class="text-xs text-amber-800">
                        <strong>Tips:</strong> Gunakan gambar dengan latar belakang transparan (PNG) untuk hasil terbaik. Stempel akan menindih tanda tangan pada halaman dokumen.
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 rounded-b-lg">
            <button @click="submitSignature()" :disabled="!signaturePreview" :class="signaturePreview ? 'bg-green-600 hover:bg-green-500' : 'bg-slate-300 cursor-not-allowed'" class="inline-flex w-full justify-center rounded-md px-4 py-2 text-sm font-semibold text-white shadow-sm sm:ml-3 sm:w-auto">
                <i class="fa-solid fa-check mr-2"></i> Simpan Tanda Tangan
            </button>
            <button @click="showSignModal=false;signaturePreview=null;stampPreview=null" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto">Batal</button>
        </div>
    </div>
</div>
