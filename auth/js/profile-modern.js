(function () {
  const state = window.__PROFILE__ || {};

  const profileImg = document.getElementById('profileImage');
  const uploadInput = document.getElementById('profileUpload');

  const sameAsLocation = document.getElementById('sameAsLocation');
  const locationInput = document.getElementById('locationInput');
  const shippingInput = document.getElementById('shippingInput');

  const toast = document.getElementById('toast');
  const toastText = document.getElementById('toastText');

  function showToast(message) {
    if (!toast || !toastText) return;
    toastText.textContent = message;
    toast.classList.remove('hidden');
    toast.classList.add('show');

    window.clearTimeout(showToast._t1);
    window.clearTimeout(showToast._t2);

    showToast._t1 = window.setTimeout(() => {
      toast.classList.remove('show');
      showToast._t2 = window.setTimeout(() => {
        toast.classList.add('hidden');
      }, 300);
    }, 2800);
  }

  if (uploadInput && profileImg) {
    uploadInput.addEventListener('change', function (e) {
      const file = e.target.files && e.target.files[0];
      if (!file) return;

      const reader = new FileReader();
      reader.onload = function (ev) {
        profileImg.src = ev.target && ev.target.result ? ev.target.result : profileImg.src;
        showToast('Profile picture preview updated');
      };
      reader.readAsDataURL(file);
    });
  }

  const genres = Array.isArray(state.genres) ? state.genres : [
    'Sci-Fi',
    'Fantasy',
    'Mystery',
    'Thriller',
    'Biography',
    'Self-Help'
  ];

  let selectedGenres = Array.isArray(state.selectedGenres) ? state.selectedGenres.slice() : [];

  function renderPreviewGenres() {
    const preview = document.getElementById('previewGenres');
    if (!preview) return;
    preview.innerHTML = '';
    selectedGenres.forEach((g) => {
      const el = document.createElement('span');
      el.className = 'text-xs bg-zinc-800 text-yellow-400 px-4 py-2 rounded-3xl';
      el.textContent = g;
      preview.appendChild(el);
    });
  }

  function toggleGenre(genre, pill) {
    if (selectedGenres.includes(genre)) {
      selectedGenres = selectedGenres.filter((g) => g !== genre);
      pill.classList.remove('active');
    } else {
      selectedGenres.push(genre);
      pill.classList.add('active');
    }
    renderPreviewGenres();
  }

  function renderGenres() {
    const container = document.getElementById('genreContainer');
    if (!container) return;

    container.innerHTML = '';

    genres.forEach((genre) => {
      const active = selectedGenres.includes(genre);
      const pill = document.createElement('button');
      pill.type = 'button';
      pill.className = `genre-pill px-6 py-3 rounded-3xl text-sm font-medium border ${active ? 'active border-transparent' : 'border-zinc-700 hover:border-zinc-500'}`;
      pill.textContent = genre;
      pill.addEventListener('click', () => toggleGenre(genre, pill));
      container.appendChild(pill);
    });

    renderPreviewGenres();
  }

  function togglePasswordSection() {
    const section = document.getElementById('passwordSection');
    const chevron = document.getElementById('passwordChevron');
    if (!section || !chevron) return;
    section.classList.toggle('hidden');
    chevron.classList.toggle('rotate-180');
  }

  window.togglePasswordSection = togglePasswordSection;

  function viewOrder(orderId) {
    showToast(`Opening order #${String(orderId).padStart(4, '0')} details...`);
  }

  window.viewOrder = viewOrder;

  if (sameAsLocation && locationInput && shippingInput) {
    sameAsLocation.addEventListener('change', function () {
      if (this.checked && shippingInput.value.trim() !== '') {
        locationInput.value = shippingInput.value;
        showToast('Location updated to match shipping');
      }
    });

    shippingInput.addEventListener('input', function () {
      if (sameAsLocation.checked) {
        locationInput.value = this.value;
      }
    });
  }

  document.addEventListener('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
      const form = document.getElementById('profileForm');
      if (form) {
        e.preventDefault();
        form.requestSubmit();
      }
    }
  });

  window.addEventListener('load', function () {
    renderGenres();

    if (state.welcomeName) {
      window.setTimeout(() => {
        showToast(`Welcome back, ${state.welcomeName}`);
      }, 600);
    }
  });
})();
