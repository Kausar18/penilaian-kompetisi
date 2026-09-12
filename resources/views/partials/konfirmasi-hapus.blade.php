{{-- Modal konfirmasi hapus dipakai bersama (dipicu lewat atribut data-hapus-* pada tombol). --}}
<div class="modal fade" id="modalHapus" tabindex="-1" aria-labelledby="hapusJudul" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0" style="border-radius: 1rem; overflow: hidden;">
            <div class="modal-body p-4">
                <div class="d-flex gap-3 mb-3">
                    <span class="hapus-ikon"><i class="bi bi-trash3"></i></span>
                    <div>
                        <h2 class="h5 mb-1" id="hapusJudul">Hapus data</h2>
                        <p class="small mb-0" style="color: var(--redup);">
                            Tindakan ini permanen dan tidak bisa dibatalkan.
                        </p>
                    </div>
                </div>

                <div class="hapus-ringkas mb-3">
                    <div class="fw-semibold" id="hapusNama">—</div>
                    <div class="small" id="hapusSub" style="color: var(--redup);" hidden></div>
                </div>

                <ul class="hapus-rincian small mb-3" id="hapusRincian" hidden></ul>

                <div id="hapusKetikWrap">
                    <label class="small d-block mb-1" for="hapusKetik">
                        Ketik ulang <span class="fw-semibold" id="hapusFrasa"></span> untuk mengonfirmasi:
                    </label>
                    <input type="text" class="form-control" id="hapusKetik" autocomplete="off"
                           spellcheck="false" autocapitalize="off">
                </div>
            </div>

            <div class="modal-footer border-0 pt-0 px-4 pb-4">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="hapusKonfirmasi" disabled>
                    <i class="bi bi-trash3 me-1"></i> Hapus Permanen
                </button>
            </div>

            <form id="hapusForm" method="POST" class="d-none">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>
</div>
