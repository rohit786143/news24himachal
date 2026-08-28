        </main>
    </div>
</div>

<!-- Quill Rich Text Editor JS -->
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

<script>
// Sidebar Mobile Toggle
const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
const adminSidebar = document.getElementById('adminSidebar');
if (sidebarToggleBtn && adminSidebar) {
    if (window.innerWidth <= 992) {
        sidebarToggleBtn.style.display = 'inline-flex';
    }
    sidebarToggleBtn.addEventListener('click', () => {
        adminSidebar.classList.toggle('open');
    });
}

// Global Delete Confirmation
function confirmDelete(url, itemLabel = 'this item') {
    if (confirm(`Are you sure you want to delete ${itemLabel}? This action cannot be undone.`)) {
        window.location.href = url;
    }
}

// Auto Slugify Generator with Hindi / Devanagari to English Transliteration
function generateSlug(text) {
    if (!text) return '';
    
    const devanagariMap = {
        'क': 'k', 'ख': 'kh', 'ग': 'g', 'घ': 'gh', 'ङ': 'n',
        'च': 'ch', 'छ': 'chh', 'ज': 'j', 'झ': 'jh', 'ञ': 'n',
        'ट': 't', 'ठ': 'th', 'ड': 'd', 'ढ': 'dh', 'ण': 'n',
        'त': 't', 'थ': 'th', 'द': 'd', 'ध': 'dh', 'न': 'n',
        'प': 'p', 'फ': 'ph', 'ब': 'b', 'भ': 'bh', 'म': 'm',
        'य': 'y', 'र': 'r', 'ल': 'l', 'व': 'v', 'श': 'sh', 'ष': 'sh', 'स': 's', 'ह': 'h',
        'क्ष': 'ksh', 'त्र': 'tr', 'ज्ञ': 'gy', 'ड़': 'd', 'ढ़': 'dh', 'फ़': 'f', 'ज़': 'z',
        'अ': 'a', 'आ': 'aa', 'इ': 'i', 'ई': 'ee', 'उ': 'u', 'ऊ': 'oo', 'ए': 'e', 'ऐ': 'ai', 'ओ': 'o', 'औ': 'au',
        'ा': 'a', 'ि': 'i', 'ी': 'ee', 'ु': 'u', 'ू': 'oo', 'े': 'e', 'ै': 'ai', 'ो': 'o', 'ौ': 'au',
        'ं': 'n', 'ँ': 'n', 'ः': 'h', 'ृ': 'ri', '्': '', '़': ''
    };

    let result = '';
    for (let i = 0; i < text.length; i++) {
        let char = text[i];
        if (devanagariMap[char] !== undefined) {
            result += devanagariMap[char];
        } else {
            result += char;
        }
    }

    return result.toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '') // remove invalid non-alphanumeric chars
        .replace(/\s+/g, '-')         // collapse whitespace to dash
        .replace(/-+/g, '-')         // collapse multiple dashes
        .replace(/^-+|-+$/g, '');     // trim leading & trailing dashes
}
</script>

</body>
</html>
