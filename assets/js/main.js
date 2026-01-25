document.addEventListener('click', e => {

    const card = e.target.closest('.event-card');
    if (!card) return;

    if (e.target.closest('button, a')) return;

    const flip = card.querySelector('.event-flip');
    if (!flip) return;

    flip.classList.toggle('flipped');

    if (!flip.classList.contains('flipped')) return;

    flip.addEventListener('transitionend', function handler(e) {
        if (e.propertyName !== 'transform') return;

        const qr = card.querySelector('.event-qr');
        if (!qr || qr.dataset.generated) return;

        new QRCode(qr, {
            text: `${location.origin}/#event-${card.dataset.id}`,
            width: 200,
            height: 200,
            useSVG: true
        });


        qr.dataset.generated = 'true';
    });

});
