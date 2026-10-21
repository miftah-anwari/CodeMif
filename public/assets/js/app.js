document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('input[type="password"]').forEach((input) => {
    const wrapper = document.createElement('span');
    wrapper.className = 'password-input-wrap';
    input.parentNode.insertBefore(wrapper, input);
    wrapper.append(input);

    const toggle = document.createElement('button');
    toggle.className = 'password-visibility-toggle';
    toggle.type = 'button';
    toggle.textContent = 'Tampilkan';
    toggle.setAttribute('aria-label', 'Tampilkan password');
    toggle.setAttribute('aria-pressed', 'false');
    wrapper.append(toggle);

    toggle.addEventListener('click', () => {
      const isVisible = input.type === 'text';
      input.type = isVisible ? 'password' : 'text';
      toggle.textContent = isVisible ? 'Tampilkan' : 'Sembunyikan';
      toggle.setAttribute('aria-label', `${isVisible ? 'Tampilkan' : 'Sembunyikan'} password`);
      toggle.setAttribute('aria-pressed', String(!isVisible));
    });
  });

  document.querySelectorAll('[data-school-class-picker]').forEach((picker) => {
    const schoolSelect = picker.querySelector('[data-school-select]');
    const classSelect = picker.querySelector('[data-class-select]');
    const optionsUrl = picker.dataset.optionsUrl;
    if (!schoolSelect || !classSelect || !optionsUrl) return;

    let availableClasses = Array.from(classSelect.options)
      .filter((option) => option.dataset.schoolId)
      .map((option) => ({
        id: option.value,
        schoolId: option.dataset.schoolId,
        name: option.textContent,
      }));
    const schoolLabel = (school) => school.admin_name || school.name;

    const updateClassOptions = (resetSelection = false) => {
      const schoolId = schoolSelect.value;
      const selectedClassId = resetSelection ? '' : classSelect.value;
      const schoolClasses = availableClasses.filter((schoolClass) => schoolClass.schoolId === schoolId);
      const placeholder = document.createElement('option');
      placeholder.value = '';
      placeholder.textContent = schoolId
        ? (schoolClasses.length ? 'Pilih kelas (opsional)' : 'Belum ada kelas aktif')
        : 'Pilih sekolah terlebih dahulu';
      classSelect.replaceChildren(placeholder);

      schoolClasses.forEach((schoolClass) => {
        const option = document.createElement('option');
        option.value = schoolClass.id;
        option.textContent = schoolClass.name;
        option.dataset.schoolId = schoolClass.schoolId;
        classSelect.append(option);
      });

      classSelect.value = schoolClasses.some((schoolClass) => schoolClass.id === selectedClassId)
        ? selectedClassId
        : '';
      classSelect.disabled = schoolId === '' || schoolClasses.length === 0;
    };

    schoolSelect.addEventListener('change', () => updateClassOptions(true));
    updateClassOptions();

    const refreshSchoolOptions = async () => {
      if (picker.contains(document.activeElement)) return;
      try {
        const requestUrl = new URL(optionsUrl, window.location.href);
        requestUrl.searchParams.set('_', String(Date.now()));
        const response = await fetch(requestUrl, {
          cache: 'no-store',
          credentials: 'same-origin',
          headers: { Accept: 'application/json' },
        });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const data = await response.json();
        if (!Array.isArray(data.schools) || !Array.isArray(data.classes)) {
          throw new Error('Respons daftar sekolah dan kelas tidak valid.');
        }

        const selectedSchoolId = schoolSelect.value;
        const schoolPlaceholder = document.createElement('option');
        schoolPlaceholder.value = '';
        schoolPlaceholder.textContent = 'Pilih sekolah';
        schoolSelect.replaceChildren(schoolPlaceholder);
        data.schools.forEach((school) => {
          const option = document.createElement('option');
          option.value = String(school.id);
          option.textContent = schoolLabel(school);
          option.dataset.adminName = school.admin_name || '';
          schoolSelect.append(option);
        });
        schoolSelect.value = data.schools.some((school) => String(school.id) === selectedSchoolId)
          ? selectedSchoolId
          : '';

        availableClasses = data.classes.map((schoolClass) => ({
          id: String(schoolClass.id),
          schoolId: String(schoolClass.school_id),
          name: schoolClass.name,
        }));
        updateClassOptions();
      } catch (error) {
        console.error('Daftar sekolah dan kelas gagal diperbarui.', error);
      }
    };

    picker.addEventListener('focusout', () => {
      window.setTimeout(refreshSchoolOptions, 0);
    });
    refreshSchoolOptions();
    window.setInterval(refreshSchoolOptions, 20000);
    document.addEventListener('visibilitychange', () => {
      if (document.visibilityState === 'visible') refreshSchoolOptions();
    });
  });

  const avatarForm = document.querySelector('.profile-avatar-form');
  const avatarInput = avatarForm?.querySelector('input[type="file"]');
  const avatarButton = avatarForm?.querySelector('.profile-avatar-change');
  if (avatarForm && avatarInput && avatarButton) {
    avatarButton.addEventListener('click', () => avatarInput.click());
    avatarInput.addEventListener('change', () => {
      if (!avatarInput.files?.length) return;
      avatarButton.disabled = true;
      avatarButton.textContent = '…';
      avatarForm.requestSubmit();
    });
  }

  const toggle = document.getElementById('menuToggle');
  const sidebar = document.getElementById('sidebar');
  if (toggle && sidebar) {
    toggle.addEventListener('click', () => sidebar.classList.toggle('open'));
    document.addEventListener('click', (event) => {
      if (window.innerWidth <= 760 && sidebar.classList.contains('open') &&
          !sidebar.contains(event.target) && !toggle.contains(event.target)) {
        sidebar.classList.remove('open');
      }
    });
  }

  const notificationToggle = document.getElementById('notificationToggle');
  const notificationPanel = document.getElementById('notificationPanel');
  const notificationWrap = document.getElementById('notificationWrap');
  if (notificationToggle && notificationPanel && notificationWrap) {
    const setNotificationsOpen = (isOpen) => {
      notificationPanel.hidden = !isOpen;
      notificationToggle.setAttribute('aria-expanded', String(isOpen));
    };
    notificationToggle.addEventListener('click', () => {
      setNotificationsOpen(notificationPanel.hidden);
    });
    document.addEventListener('click', (event) => {
      if (!notificationWrap.contains(event.target)) setNotificationsOpen(false);
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        setNotificationsOpen(false);
        notificationToggle.focus();
      }
    });
  }

  document.querySelectorAll('[data-no-copy]').forEach((example) => {
    example.addEventListener('copy', (event) => event.preventDefault());
    example.addEventListener('cut', (event) => event.preventDefault());
    example.addEventListener('contextmenu', (event) => event.preventDefault());
    example.addEventListener('dragstart', (event) => event.preventDefault());
    example.addEventListener('keydown', (event) => {
      if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'c') event.preventDefault();
    });
  });
  document.addEventListener('copy', (event) => {
    const selectedNode = window.getSelection()?.anchorNode;
    if (selectedNode?.parentElement?.closest('[data-no-copy]')) event.preventDefault();
  });

  document.querySelectorAll('[data-practice-editor]').forEach((workspace) => {
    const editor = workspace.querySelector('#practiceCode');
    const runButton = workspace.querySelector('[data-run-practice]');
    const output = workspace.querySelector('[data-practice-output]');
    const status = workspace.querySelector('[data-practice-status]');
    if (!editor || !runButton || !output || !status) return;

    let activeFrame = null;
    let activeWorker = null;
    let runTimeout = 0;
    let activityTimeout = 0;
    const setOutput = (text) => { output.textContent = text || '(Tidak ada output)'; };
    editor.addEventListener('keydown', (event) => {
      if (event.key === 'Tab' && !editor.disabled) {
        event.preventDefault();
        editor.setRangeText('    ', editor.selectionStart, editor.selectionEnd, 'end');
      }
    });
    const finishRun = (message) => {
      window.clearTimeout(runTimeout);
      activeWorker?.terminate();
      activeWorker = null;
      runButton.disabled = false;
      runButton.textContent = 'Jalankan kode';
      status.textContent = message;
    };
    const trackActivity = async () => {
      const form = workspace.closest('form');
      const csrfToken = form?.querySelector('[name="csrf_token"]')?.value;
      const courseId = form?.querySelector('[name="course_id"]')?.value;
      const lessonId = form?.querySelector('[name="lesson_id"]')?.value;
      if (!csrfToken || !courseId || !lessonId) return;
      const body = new URLSearchParams({
        csrf_token: csrfToken,
        action: 'learning_heartbeat',
        course_id: courseId,
        lesson_id: lessonId,
      });
      try {
        const response = await fetch(window.location.href, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
          body,
          credentials: 'same-origin',
        });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        status.textContent = 'Aktivitas praktik tercatat.';
      } catch (error) {
        console.error('Aktivitas belajar gagal diperbarui.', error);
        status.textContent = 'Aktivitas belum tersimpan. Periksa koneksi lalu lanjutkan mengetik.';
      }
    };
    editor.addEventListener('input', () => {
      if (editor.disabled) return;
      window.clearTimeout(activityTimeout);
      activityTimeout = window.setTimeout(trackActivity, 5000);
    });
    const showPreview = (source, language) => {
      activeFrame?.remove();
      const frame = document.createElement('iframe');
      activeFrame = frame;
      frame.className = 'practice-preview';
      frame.title = `Pratinjau kode ${language}`;
      frame.setAttribute('sandbox', 'allow-scripts');
      frame.setAttribute('referrerpolicy', 'no-referrer');
      output.hidden = language === 'html' || language === 'css';
      if (language === 'html') {
        frame.srcdoc = `<!doctype html><html><head><meta charset="utf-8"><meta http-equiv="Content-Security-Policy" content="default-src 'none'; style-src 'unsafe-inline'; img-src data: blob:; form-action 'none'"></head><body>${source}</body></html>`;
      } else if (language === 'css') {
        const encodedCss = encodeURIComponent(source);
        frame.srcdoc = `<!doctype html><html><head><meta charset="utf-8"><meta http-equiv="Content-Security-Policy" content="default-src 'none'; script-src 'unsafe-inline'; style-src 'unsafe-inline'"><style id="student-css"></style></head><body><header class="site-header"><strong>Portofolio Rani</strong><nav><a href="#profil">Profil</a><a href="#proyek">Proyek</a><a href="#kontak">Kontak</a></nav></header><main class="preview"><section class="portfolio-hero"><p>WEB DEVELOPER PEMULA</p><h1>Halo, saya Rani.</h1><p>Saya sedang belajar membuat website yang bermanfaat.</p><button>Lihat proyek</button></section><section id="profil"><h2>Tentang saya</h2><p>Suka belajar HTML, CSS, dan JavaScript.</p></section><section id="proyek" class="project-grid"><article class="portfolio-card"><h2>Website Portofolio</h2><p>Halaman mini yang dibuat bertahap.</p><a href="#kontak">Lihat proyek</a></article><article class="portfolio-card"><h2>Daftar Belajar</h2><p>Contoh kartu proyek kedua.</p><a href="#kontak">Lihat proyek</a></article></section><section id="kontak"><h2>Hubungi saya</h2><label>Email <input value="rani@example.com" readonly></label></section></main><footer class="site-footer">© 2026 Rani · Website latihan</footer><script>document.getElementById('student-css').textContent=decodeURIComponent("${encodedCss}")</script></body></html>`;
      } else {
        const encodedJavaScript = encodeURIComponent(source);
        frame.srcdoc = `<!doctype html><html><head><meta charset="utf-8"><meta http-equiv="Content-Security-Policy" content="default-src 'none'; script-src 'unsafe-inline' 'unsafe-eval'; connect-src 'none'; form-action 'none'"></head><body><p>JavaScript dijalankan di iframe terisolasi.</p><script>(()=>{const send=(text)=>parent.postMessage({type:'codemif-practice-output',text},'*');const originalLog=console.log.bind(console);console.log=(...values)=>{const text=values.map(value=>{if(typeof value==='string')return value;try{return JSON.stringify(value)}catch{return String(value)}}).join(' ');send(text);originalLog(...values)};try{new Function(decodeURIComponent("${encodedJavaScript}"))()}catch(error){send('Error: '+error.message)}})()</script></body></html>`;
      }
      workspace.append(frame);
    };
    const showGeneratedHtml = (source) => {
      if (!/^<(?:!doctype\s+html|html|main|header|section)\b/i.test(source.trim())) return;
      activeFrame?.remove();
      const frame = document.createElement('iframe');
      activeFrame = frame;
      frame.className = 'practice-preview';
      frame.title = 'Pratinjau HTML yang dibuat Python';
      frame.setAttribute('sandbox', '');
      frame.setAttribute('referrerpolicy', 'no-referrer');
      frame.srcdoc = `<!doctype html><html><head><meta charset="utf-8"><meta http-equiv="Content-Security-Policy" content="default-src 'none'; style-src 'unsafe-inline'; img-src data:"></head><body>${source}</body></html>`;
      workspace.append(frame);
    };

    window.addEventListener('message', (event) => {
      if (activeFrame && event.source === activeFrame.contentWindow && event.data?.type === 'codemif-practice-output') {
        output.textContent += `${String(event.data.text)}\n`;
      }
    });

    runButton.addEventListener('click', () => {
      const source = editor.value;
      if (!source.trim()) {
        status.textContent = 'Ketik kode terlebih dahulu sebelum menjalankan.';
        editor.focus();
        return;
      }
      const language = (workspace.dataset.language || '').toLowerCase();
      runButton.disabled = true;
      runButton.textContent = 'Menjalankan...';
      setOutput('');
      if (language === 'python' || language === 'py') {
        activeFrame?.remove();
        activeFrame = null;
        output.hidden = false;
        status.textContent = 'Menyiapkan Python di browser; pemakaian pertama mengunduh runtime beberapa MB.';
        const script = document.querySelector('script[src*="assets/js/app.js"]');
        const workerUrl = new URL('./python-practice-worker.js', script?.src || window.location.href);
        activeWorker?.terminate();
        try {
          activeWorker = new Worker(workerUrl);
        } catch (error) {
          finishRun(`Runtime Python gagal dimulai: ${error instanceof Error ? error.message : String(error)}`);
          return;
        }
        const worker = activeWorker;
        worker.addEventListener('message', (event) => {
          if (activeWorker !== worker) return;
          if (event.data?.type === 'output') output.textContent += event.data.text;
          if (event.data?.type === 'done') {
            showGeneratedHtml(output.textContent);
            finishRun('Selesai dijalankan di browser. Jika kode menghasilkan HTML, pratinjaunya tampil di bawah.');
          }
          if (event.data?.type === 'error') finishRun(`Python error: ${event.data.message}`);
          if (event.data?.type === 'ready') status.textContent = 'Python siap; kode tetap berjalan di browser.';
        });
        worker.addEventListener('error', (event) => {
          if (activeWorker === worker) finishRun(`Runtime Python gagal dimuat: ${event.message}`);
        });
        runTimeout = window.setTimeout(() => finishRun('Waktu eksekusi habis. Periksa apakah ada perulangan yang tidak berhenti.'), 60000);
        const inputValues = workspace.querySelector('[data-practice-input]')?.value.split(/\r?\n/) || [];
        activeWorker.postMessage({ code: source, inputs: inputValues });
        return;
      }
      if (['html', 'css', 'javascript', 'js'].includes(language)) {
        status.textContent = `Menjalankan ${language.toUpperCase()} dalam pratinjau browser terisolasi.`;
        showPreview(source, language === 'js' ? 'javascript' : language);
        if (language === 'javascript' || language === 'js') output.hidden = false;
        runButton.disabled = false;
        runButton.textContent = 'Jalankan kode';
        return;
      }
      finishRun(`Runner browser belum mendukung ${language.toUpperCase()}. Kode tetap bisa diketik dan dikirim kepada admin.`);
    });

    workspace.closest('form')?.addEventListener('submit', (event) => {
      if (!editor.value.trim()) {
        event.preventDefault();
        status.textContent = 'Ketik hasil praktik sebelum mengirim kepada admin.';
        editor.focus();
      }
    });
  });

  document.querySelectorAll('[data-tutor-suggestion]').forEach((button) => {
    button.addEventListener('click', () => {
      const input = document.getElementById('tutorQuestion');
      if (!input) return;
      input.value = button.dataset.tutorSuggestion || '';
      input.focus();
    });
  });

  if (window.location.hash === '#tutor-chat') {
    const tutorChat = document.getElementById('tutor-chat');
    const tutorMessages = tutorChat?.querySelector('.tutor-messages');
    if (tutorChat && tutorMessages) {
      requestAnimationFrame(() => {
        tutorMessages.scrollTop = tutorMessages.scrollHeight;
        tutorChat.scrollIntoView({ block: 'end' });
      });
    }
  }

  const tutorForm = document.querySelector('.tutor-prompt');
  if (tutorForm) {
    let tutorSending = false;
    tutorForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      if (tutorSending) return;

      const input = tutorForm.querySelector('#tutorQuestion');
      const messages = document.querySelector('.tutor-messages');
      const button = tutorForm.querySelector('button[type="submit"]');
      const question = input?.value.trim() || '';
      if (!input || !messages || !button || !question) return;

      tutorSending = true;
      const originalButtonHtml = button.innerHTML;
      button.disabled = true;
      button.textContent = 'Menyiapkan...';
      messages.querySelector('.tutor-suggestions')?.remove();

      const userMessage = document.createElement('article');
      userMessage.className = 'tutor-message user';
      const userName = document.createElement('strong');
      userName.textContent = 'Kamu';
      const userText = document.createElement('p');
      userText.textContent = question;
      userMessage.append(userName, userText);
      messages.append(userMessage);

      const assistantMessage = document.createElement('article');
      assistantMessage.className = 'tutor-message assistant';
      assistantMessage.classList.add('is-generating');
      const assistantName = document.createElement('strong');
      assistantName.textContent = 'Tutor AI';
      const assistantText = document.createElement('p');
      assistantText.textContent = 'Menyiapkan jawaban...';
      assistantMessage.append(assistantName, assistantText);
      messages.append(assistantMessage);
      messages.scrollTop = messages.scrollHeight;

      let hasContent = false;
      let completed = false;
      let streamFinished = false;
      let animationFrame = 0;
      let lastFrameTime = 0;
      let elapsedTypingTime = 0;
      const pendingCharacters = [];
      let resolveTyping;
      const typingFinished = new Promise((resolve) => { resolveTyping = resolve; });
      const finishTypingIfReady = () => {
        if (streamFinished && pendingCharacters.length === 0) {
          assistantMessage.classList.remove('is-generating');
          resolveTyping();
          return true;
        }
        return false;
      };
      const animateTyping = (timestamp) => {
        animationFrame = 0;
        if (!lastFrameTime) lastFrameTime = timestamp;
        elapsedTypingTime += timestamp - lastFrameTime;
        lastFrameTime = timestamp;

        const charactersToShow = Math.min(pendingCharacters.length, Math.floor(elapsedTypingTime / 24));
        if (charactersToShow > 0) {
          const visibleText = pendingCharacters.splice(0, charactersToShow).join('');
          elapsedTypingTime %= 24;
          assistantText.textContent = hasContent ? assistantText.textContent + visibleText : visibleText;
          hasContent = true;
          messages.scrollTop = messages.scrollHeight;
        }

        if (pendingCharacters.length > 0) {
          animationFrame = window.requestAnimationFrame(animateTyping);
        } else {
          lastFrameTime = 0;
          finishTypingIfReady();
        }
      };
      const queueTyping = (text) => {
        pendingCharacters.push(...Array.from(text));
        if (!animationFrame) animationFrame = window.requestAnimationFrame(animateTyping);
      };
      const handleLine = (line) => {
        if (!line.trim()) return;
        const eventData = JSON.parse(line);
        if (eventData.type === 'chunk' && typeof eventData.content === 'string') {
          if (!hasContent && pendingCharacters.length === 0) assistantText.textContent = '';
          queueTyping(eventData.content);
        } else if (eventData.type === 'error') {
          throw new Error(eventData.message || 'Tutor AI gagal menjawab.');
        } else if (eventData.type === 'done') {
          completed = true;
          streamFinished = true;
          finishTypingIfReady();
        }
      };

      try {
        const response = await fetch(tutorForm.getAttribute('action') || window.location.href, {
          method: 'POST',
          body: new FormData(tutorForm),
          headers: { Accept: 'application/x-ndjson' },
          credentials: 'same-origin',
        });
        const contentType = response.headers.get('content-type') || '';
        if (!contentType.includes('application/x-ndjson')) {
          if (response.redirected) {
            throw new Error('Sesi login berakhir. Silakan masuk kembali, lalu coba lagi.');
          }
          throw new Error(`Server mengirim respons yang tidak sesuai (HTTP ${response.status}). Muat ulang halaman lalu coba lagi.`);
        }
        if (!response.body) throw new Error('Browser tidak mendukung jawaban streaming.');

        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';
        while (true) {
          const { value, done } = await reader.read();
          if (done) break;
          buffer += decoder.decode(value, { stream: true });
          const lines = buffer.split('\n');
          buffer = lines.pop() || '';
          lines.forEach(handleLine);
        }
        buffer += decoder.decode();
        if (buffer.trim()) handleLine(buffer);
        if (!completed) throw new Error('Koneksi terputus sebelum jawaban selesai.');
        await typingFinished;
        input.value = '';
      } catch (error) {
        streamFinished = true;
        finishTypingIfReady();
        if (pendingCharacters.length > 0) {
          if (!animationFrame) animationFrame = window.requestAnimationFrame(animateTyping);
          await typingFinished;
        }
        assistantMessage.classList.add('tutor-stream-failed');
        const errorText = error instanceof Error ? error.message : 'Tutor AI gagal menjawab.';
        assistantText.textContent = hasContent
          ? `${assistantText.textContent}\n\nKoneksi terputus: ${errorText}`
          : errorText;
      } finally {
        if (animationFrame) window.cancelAnimationFrame(animationFrame);
        tutorSending = false;
        button.disabled = false;
        button.innerHTML = originalButtonHtml;
        input.focus();
        messages.scrollTop = messages.scrollHeight;
      }
    });
  }

  document.querySelectorAll('[data-confirm]').forEach((link) => {
    link.addEventListener('click', (event) => {
      if (!window.confirm(link.dataset.confirm || 'Lanjutkan?')) event.preventDefault();
    });
  });

  document.querySelectorAll('form').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (form.classList.contains('review-form') || form.classList.contains('tutor-prompt')) return;
      const button = event.submitter || form.querySelector('button[type="submit"]');
      if (button && !button.disabled) {
        button.dataset.originalText = button.textContent;
        button.disabled = true;
        button.textContent = 'Memproses...';
      }
    });
  });
});
