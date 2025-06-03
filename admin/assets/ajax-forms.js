document.addEventListener('DOMContentLoaded', () => {
  const ajaxForms = document.querySelectorAll('form.ajax-form');

  ajaxForms.forEach(form => {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      const submitButton = form.querySelector('button[type="submit"]');
      submitButton.disabled = true;

      const responseDiv = form.querySelector('.response-message');

      try {
        // Prepare form data
        const formData = new FormData(form);

        // Make fetch with Authorization header including API token
        const res = await fetch(form.action, {
          method: form.method,
          headers: {
            'Authorization': 'Bearer ' + window.SIGNALFRAME_API_TOKEN
          },
          body: formData,
        });

        const json = await res.json();

        if (json.success) {
          responseDiv.textContent = 'Update successful!';
          responseDiv.classList.add('success');
          responseDiv.classList.remove('error');
        } else {
          responseDiv.textContent = 'Update failed: ' + (json.error || 'Unknown error');
          responseDiv.classList.add('error');
          responseDiv.classList.remove('success');
        }
      } catch (err) {
        responseDiv.textContent = 'Request error: ' + err.message;
        responseDiv.classList.add('error');
        responseDiv.classList.remove('success');
      } finally {
        submitButton.disabled = false;
      }
    });
  });
});
