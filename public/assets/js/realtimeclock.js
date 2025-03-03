
    let clock = document.getElementById('clock');
 
    function RealtimeClock() {
        const karon = new Date();
        const tuig = karon.getFullYear();
        const bulan = karon.toLocaleString('en-US', { month: 'long' });
        const adlaw = String(karon.getDate()).padStart(2, '0'); 
        let oras = karon.getHours();
        const minuto = String(karon.getMinutes()).padStart(2, '0');
        const segundo = String(karon.getSeconds()).padStart(2, '0');
        const amOrPm = oras >= 12 ? 'PM' : 'AM';
        oras = oras % 12 || 12; 
        clock.innerHTML = `${bulan} ${adlaw}, ${tuig} - ${oras}:${minuto}:${segundo} ${amOrPm}`;
;
    }

    setInterval(RealtimeClock, 1000);
    RealtimeClock();