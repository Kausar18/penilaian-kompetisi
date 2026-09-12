{{-- Animasi hitung-naik (0 → angka tujuan) untuk .statistik-angka di dalam kartu. --}}
<script>
(function () {
    const els = Array.from(document.querySelectorAll('.statistik-angka'));
    if (!els.length) return;

    const diam = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const durasi = 1400;
    const fmt = (n) => new Intl.NumberFormat('id-ID').format(n);
    const easeOut = (t) => 1 - Math.pow(1 - t, 4);

    // siapkan tiap angka: simpan target, tampilkan 0 dulu
    const antre = [];
    els.forEach((el) => {
        const asli = el.textContent.trim();
        const cocok = asli.match(/^(\D*)([\d.]+)(\D*)$/);
        if (!cocok) return;

        const target = parseInt(cocok[2].replace(/\D/g, ''), 10);
        if (!Number.isFinite(target) || target <= 0) return;

        const item = { el, asli, awalan: cocok[1], akhiran: cocok[3], target, jalan: false };
        if (!diam) el.textContent = item.awalan + '0' + item.akhiran;
        antre.push(item);
    });
    if (!antre.length || diam) return;

    function main(item, tunda) {
        if (item.jalan) return;
        item.jalan = true;
        const mulai = performance.now() + tunda;

        function langkah(now) {
            const p = Math.max(0, Math.min(1, (now - mulai) / durasi));
            item.el.textContent = item.awalan + fmt(Math.round(easeOut(p) * item.target)) + item.akhiran;
            if (p < 1) {
                requestAnimationFrame(langkah);
            } else {
                item.el.textContent = item.asli; // pastikan persis seperti render server
            }
        }
        requestAnimationFrame(langkah);
    }

    // jalankan saat kartu masuk layar (biar terlihat), dengan sedikit jeda beruntun
    const io = new IntersectionObserver((entries, obs) => {
        entries.forEach((e) => {
            if (!e.isIntersecting) return;
            const item = antre.find((i) => i.el === e.target);
            if (item) {
                const idxTerlihat = antre.filter((i) => i.jalan).length;
                main(item, idxTerlihat * 90);
            }
            obs.unobserve(e.target);
        });
    }, { threshold: 0.4 });

    antre.forEach((i) => io.observe(i.el));
})();
</script>
