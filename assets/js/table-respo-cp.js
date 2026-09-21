 // Filter chips interaction
    document.querySelectorAll('.filter-chip').forEach(chip => {
      chip.addEventListener('click', () => {
        document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
        chip.classList.add('active');
      });
    });
 
    // Header sort interaction
    document.querySelectorAll('.gtable__cell--head').forEach(h => {
      h.addEventListener('click', () => {
        document.querySelectorAll('.gtable__cell--head').forEach(c => c.classList.remove('sorted'));
        if (h.textContent.trim()) h.classList.add('sorted');
      });
    });
 
    // Pagination interaction
    document.querySelectorAll('.page-btn:not([disabled])').forEach(btn => {
      if (!btn.querySelector('svg')) {
        btn.addEventListener('click', () => {
          document.querySelectorAll('.page-btn').forEach(b => b.classList.remove('active'));
          btn.classList.add('active');
        });
      }
    });