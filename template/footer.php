      </div>

      <!-- Footer -->
      <footer class="border-top px-3 py-2 text-center text-muted small">
        &copy; <?= date('Y') ?> Sriwijaya Grafika &mdash; Sistem Administratif
      </footer>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js" defer></script>
  <script>
  function switchEntity(entityId, entityName) {
    var formData = new FormData();
    formData.append('entity_id', entityId);
    fetch('<?= BASE_URL ?>/api/set_entity.php', {
      method: 'POST',
      body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data.success) {
        var el = document.getElementById('entityNameDisplay');
        if (el) el.textContent = entityName;
        location.reload();
      } else {
        alert('Gagal: ' + (data.error || 'Unknown error'));
      }
    })
    .catch(function() { alert('Gagal menghubungi server'); });
  }
  </script>
  <script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
