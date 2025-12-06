// Custom confirmation modal for MHSA system
// Usage: showConfirmBox(message, callback)

function showConfirmBox(message, onConfirm, onCancel) {
    // Remove any existing modal
    const old = document.getElementById('mhsa-confirm-modal');
    if (old) old.remove();

    // Modal overlay
    const overlay = document.createElement('div');
    overlay.id = 'mhsa-confirm-modal';
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

    // Buttons
    const btnRow = document.createElement('div');
    btnRow.style.display = 'flex';
    btnRow.style.justifyContent = 'center';
    btnRow.style.gap = '18px';

    const yesBtn = document.createElement('button');
    yesBtn.innerText = 'Yes';
    yesBtn.style.background = 'linear-gradient(135deg,#8e44ad,#9b59b6)';
    yesBtn.style.color = 'white';
    yesBtn.style.border = 'none';
    yesBtn.style.borderRadius = '8px';
    yesBtn.style.padding = '10px 28px';
    yesBtn.style.fontWeight = 'bold';
    yesBtn.style.fontSize = '16px';
    yesBtn.style.cursor = 'pointer';
    yesBtn.onclick = function() {
        overlay.remove();
        if (onConfirm) onConfirm();
    };

    const noBtn = document.createElement('button');
    noBtn.innerText = 'Cancel';
    noBtn.style.background = '#f5f1ff';
    noBtn.style.color = '#8e44ad';
    noBtn.style.border = '1.5px solid #8e44ad';
    noBtn.style.borderRadius = '8px';
    noBtn.style.padding = '10px 28px';
    noBtn.style.fontWeight = 'bold';
    noBtn.style.fontSize = '16px';
    noBtn.style.cursor = 'pointer';
    noBtn.onclick = function() {
        overlay.remove();
        if (onCancel) onCancel();
    };

    btnRow.appendChild(yesBtn);
    btnRow.appendChild(noBtn);
    box.appendChild(btnRow);

    overlay.appendChild(box);
    document.body.appendChild(overlay);
}

// To use, replace confirm() with showConfirmBox('Your message', function(){ ... }, function(){ ... })
