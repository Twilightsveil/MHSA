// Custom alert modal for MHSA system
// Usage: showAlertBox(message, callback)

function showAlertBox(message, onClose) {
    // Remove any existing modal
    const old = document.getElementById('mhsa-alert-modal');
    if (old) old.remove();

    // Modal overlay
    const overlay = document.createElement('div');
    overlay.id = 'mhsa-alert-modal';
    overlay.style.position = 'fixed';
    overlay.style.top = 0;
    overlay.style.left = 0;
    overlay.style.width = '100vw';
    overlay.style.height = '100vh';
    overlay.style.background = 'rgba(60, 30, 90, 0.25)';
    overlay.style.display = 'flex';
    overlay.style.alignItems = 'center';
    overlay.style.justifyContent = 'center';
    overlay.style.zIndex = 9999;

    // Modal box
    const box = document.createElement('div');
    box.style.background = 'white';
    box.style.borderRadius = '18px';
    box.style.boxShadow = '0 8px 40px rgba(142,68,173,0.18)';
    box.style.padding = '36px 32px 28px 32px';
    box.style.minWidth = '320px';
    box.style.maxWidth = '90vw';
    box.style.textAlign = 'center';
    box.style.border = '2px solid #8e44ad';
    box.style.position = 'relative';

    // Message
    const msg = document.createElement('div');
    msg.style.fontSize = '18px';
    msg.style.color = '#4b2b63';
    msg.style.marginBottom = '28px';
    msg.innerText = message;
    box.appendChild(msg);

    // OK Button
    const okBtn = document.createElement('button');
    okBtn.innerText = 'OK';
    okBtn.style.background = 'linear-gradient(135deg,#8e44ad,#9b59b6)';
    okBtn.style.color = 'white';
    okBtn.style.border = 'none';
    okBtn.style.borderRadius = '8px';
    okBtn.style.padding = '10px 28px';
    okBtn.style.fontWeight = 'bold';
    okBtn.style.fontSize = '16px';
    okBtn.style.cursor = 'pointer';
    okBtn.onclick = function() {
        overlay.remove();
        if (onClose) onClose();
    };
    box.appendChild(okBtn);

    overlay.appendChild(box);
    document.body.appendChild(overlay);
}

// To use, replace alert() with showAlertBox('Your message', function(){ ... })
