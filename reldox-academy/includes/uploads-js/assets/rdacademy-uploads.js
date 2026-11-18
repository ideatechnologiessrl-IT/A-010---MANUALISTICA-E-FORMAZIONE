/* ============================================================================
   RelDoxAcademy — componente allegati riusabile (window.RdacademyUploads)
   ----------------------------------------------------------------------------
   Copiato da reldox-uploads.js (pkg-anagrafica-1b3-uploads-js), RelDox CRD
   3.55.09G, e ridotto. Componenti vanilla, zero dipendenze:
     - RdacademyUploads.DropZone : area di caricamento drag&drop + piu' file
     - RdacademyUploads.List     : elenco allegati con aggiorna, scarica, elimina
     - RdacademyUploads.Banner   : avviso «contenuto gia' presente»
     - RdacademyUploads.Toast    : esiti non bloccanti

   API REST consumata (rdacademy/v1/uploads): POST /attach, GET /list,
   GET /{id}/download, POST /{id}/delete, GET /{id}/meta.

   DIFFERENZE DICHIARATE:
     - ⛔ NIENTE window.confirm (REGOLE § 4): l eliminazione si conferma DENTRO
       la riga, che si trasforma in domanda con «Elimina» e «Annulla».
     - le scritture portano anche X-RDACADEMY-Nonce (gettone del canale dell app,
       letto da window.RDACADEMY.nonce): i REST del prodotto lo pretendono.
     - /soft-delete si chiama /delete (toglie anche il file all ultimo riferimento).
     - gli errori del Toast restano finche' li chiudi (REGOLE § 4: gli errori
       restano, gli esiti spariscono).
     - TAGLIO: la barra di selezione multipla (BUG-116 del CRM), l apertura dei
       PDF in nuova scheda (resta il download, il server li serve inline).
   ============================================================================ */

(function (global) {
	'use strict';

	// ─────────────────────────────────────────────────────────────────────
	// CONFIG + I18N
	// ─────────────────────────────────────────────────────────────────────

	/**
	 * Acquisizione lazy della config emessa dal PHP loader.
	 * Se window.RdacademyUploads.config non è presente (caso patologico),
	 * forniamo defaults sicuri che falliranno garbatamente.
	 */
	function getConfig() {
		var cfg = (global.RdacademyUploads && global.RdacademyUploads.config) || {};
		return {
			apiBase: cfg.apiBase || '/wp-json/rdacademy/v1/uploads',
			nonce: cfg.nonce || '',
			maxSize: cfg.maxSize || (20 * 1024 * 1024),
			allowedExtensions: cfg.allowedExtensions || [],
			allowedMimes: cfg.allowedMimes || [],
			maxConcurrent: cfg.maxConcurrent || 3,
			i18n: cfg.i18n || {},
			version: cfg.version || '3.9.4'
		};
	}

	/**
	 * Stringa i18n con interpolazione %TOKEN%.
	 */
	function t(key, vars) {
		var cfg = getConfig();
		var s = (cfg.i18n && cfg.i18n[key]) || key;
		if (vars) {
			for (var k in vars) {
				if (Object.prototype.hasOwnProperty.call(vars, k)) {
					s = s.split('%' + k.toUpperCase() + '%').join(String(vars[k]));
				}
			}
		}
		return s;
	}

	// ─────────────────────────────────────────────────────────────────────
	// HELPERS GENERICI
	// ─────────────────────────────────────────────────────────────────────

	function el(tag, attrs, children) {
		var node = document.createElement(tag);
		if (attrs) {
			for (var k in attrs) {
				if (!Object.prototype.hasOwnProperty.call(attrs, k)) continue;
				var v = attrs[k];
				if (v === null || v === undefined || v === false) continue;
				if (k === 'class') node.className = v;
				else if (k === 'html') node.innerHTML = v;
				else if (k === 'text') node.textContent = v;
				else if (k === 'on' && typeof v === 'object') {
					for (var ev in v) {
						if (Object.prototype.hasOwnProperty.call(v, ev)) {
							node.addEventListener(ev, v[ev]);
						}
					}
				}
				else if (k.indexOf('data-') === 0 || k.indexOf('aria-') === 0 || k === 'role' || k === 'tabindex' || k === 'type' || k === 'href' || k === 'target' || k === 'rel' || k === 'id' || k === 'title' || k === 'name' || k === 'value' || k === 'for' || k === 'placeholder' || k === 'multiple' || k === 'accept' || k === 'disabled' || k === 'required') {
					node.setAttribute(k, v === true ? '' : String(v));
				}
				else {
					try { node[k] = v; } catch (_) { node.setAttribute(k, String(v)); }
				}
			}
		}
		if (children) {
			if (!Array.isArray(children)) children = [children];
			children.forEach(function (c) {
				if (c === null || c === undefined) return;
				if (typeof c === 'string') node.appendChild(document.createTextNode(c));
				else node.appendChild(c);
			});
		}
		return node;
	}

	/**
	 * Formato dimensione file human-readable.
	 */
	function formatSize(bytes) {
		var n = Number(bytes) || 0;
		if (n < 1024) return n + ' ' + t('unit_bytes');
		if (n < 1024 * 1024) return (n / 1024).toFixed(1) + ' ' + t('unit_kb');
		if (n < 1024 * 1024 * 1024) return (n / (1024 * 1024)).toFixed(1) + ' ' + t('unit_mb');
		return (n / (1024 * 1024 * 1024)).toFixed(2) + ' ' + t('unit_gb');
	}

	/**
	 * Format data ISO → locale it_IT compatto "23/05/2026 19:12".
	 * Accetta sia "2026-05-23 19:12:34" sia "2026-05-23T19:12:34.000Z".
	 */
	function formatDate(iso) {
		if (!iso) return '';
		try {
			var s = String(iso).replace(' ', 'T');
			// 3.54.05 (S584) S584_B236_ALLEGATI — BUG-236: uploaded_at e scritto in UTC (Rdacademy_Uploads_Entity, gmdate) e arriva
			// dal REST senza fuso: si legge come UTC e si mostra nell ora del browser. Prima si leggeva come ora locale (= l ora UTC a video).
			// Un valore che porta gia il suo fuso (Z, +02:00) resta com e. I microsecondi si tagliano a 3 cifre (Safari).
			if (/^\d{4}-\d\d-\d\dT\d\d:\d\d(:\d\d(\.\d+)?)?$/.test(s)) {
				s = s.replace(/(\.\d{3})\d+$/, '$1') + 'Z';
			}
			var d = new Date(s);
			if (isNaN(d.getTime())) return String(iso);
			var pad = function (n) { return n < 10 ? '0' + n : '' + n; };
			return pad(d.getDate()) + '/' + pad(d.getMonth() + 1) + '/' + d.getFullYear()
				+ ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes());
		} catch (e) {
			return String(iso);
		}
	}

	/**
	 * Estensione filename (lowercase, senza punto). "" se assente.
	 */
	function fileExt(name) {
		var s = String(name || '');
		var i = s.lastIndexOf('.');
		if (i < 0 || i === s.length - 1) return '';
		return s.substring(i + 1).toLowerCase();
	}

	/**
	 * Validazione client-side. Restituisce null se ok, stringa errore i18n key se KO.
	 */
	function validateFile(file) {
		var cfg = getConfig();
		if (!file || file.size === 0) return 'err_empty_file';
		if (file.size > cfg.maxSize) return 'err_invalid_size';

		var ext = fileExt(file.name);
		var mime = file.type || '';

		// Defense in depth: estensione OR mime devono matchare whitelist.
		// La verifica autorevole è comunque server-side (1B.2 entity).
		var extOk = cfg.allowedExtensions.length === 0
			|| cfg.allowedExtensions.indexOf(ext) >= 0;
		var mimeOk = mime === ''
			|| cfg.allowedMimes.length === 0
			|| cfg.allowedMimes.indexOf(mime) >= 0;

		if (!extOk && !mimeOk) return 'err_invalid_mime';
		return null;
	}

	// ─────────────────────────────────────────────────────────────────────
	// ICON SET (SVG inline)
	// ─────────────────────────────────────────────────────────────────────

	var ICONS = {
		upload: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><title>Upload</title><desc>Icona caricamento file</desc><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>',
		file: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><title>File</title><desc>Icona documento generico</desc><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>',
		download: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><title>Download</title><desc>Icona download</desc><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>',
		openTab: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><title>Apri</title><desc>Apri in una nuova scheda</desc><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>',
		trash: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><title>Elimina</title><desc>Icona elimina allegato</desc><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>',
		close: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><title>Chiudi</title><desc>Chiudi notifica</desc><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
		info: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><title>Informazione</title><desc>Icona informativa</desc><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
		refresh: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><title>Aggiorna</title><desc>Aggiorna elenco</desc><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10"/><path d="M20.49 15a9 9 0 0 1-14.85 3.36L1 14"/></svg>',
		// 3.24.00 BUG-116 — le due icone dei comandi di selezione.
		checkAll: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><title>Seleziona tutti</title><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>',
		uncheckAll: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><title>Deseleziona</title><rect x="3" y="3" width="18" height="18" rx="2"/></svg>',
		emptyBox: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><title>Vuoto</title><desc>Nessun elemento</desc><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>'
	};

	function svgIcon(name, cssClass) {
		var wrap = document.createElement('span');
		wrap.className = cssClass || '';
		wrap.innerHTML = ICONS[name] || ICONS.file;
		return wrap;
	}

	// ─────────────────────────────────────────────────────────────────────
	// TOAST helper
	// ─────────────────────────────────────────────────────────────────────

	var Toast = {
		region: null,

		_ensureRegion: function () {
			if (this.region && document.body.contains(this.region)) return this.region;
			this.region = el('div', {
				'class': 'rdacademy-uploads rdacademy-uploads__toast-region',
				'role': 'status',
				'aria-live': 'polite',
				'aria-atomic': 'true'
			});
			document.body.appendChild(this.region);
			return this.region;
		},

		show: function (message, kind, duration) {
			this._ensureRegion();
			var node = el('div', {
				'class': 'rdacademy-uploads__toast rdacademy-uploads__toast--' + (kind || 'info'),
				'role': kind === 'error' ? 'alert' : null
			}, [el('span', { 'class': 'rdacademy-uploads__toast-text', 'text': String(message) })]);
			this.region.appendChild(node);
			function togli() {
				if (node && node.parentNode) {
					node.style.transition = 'opacity 200ms ease';
					node.style.opacity = '0';
					setTimeout(function () { if (node.parentNode) node.parentNode.removeChild(node); }, 220);
				}
			}
			if (kind === 'error') {
				// REGOLE § 4: un errore resta finche' lo chiudi, e si puo' copiare.
				var x = el('button', { 'class': 'rdacademy-uploads__toast-close', 'type': 'button', 'aria-label': t('duplicate_banner_close'), 'text': '×' });
				x.addEventListener('click', togli);
				node.appendChild(x);
				return node;
			}
			setTimeout(togli, typeof duration === 'number' ? duration : 4000);
			return node;
		},

		success: function (msg, ms) { return this.show(msg, 'success', ms); },
		error:   function (msg, ms) { return this.show(msg, 'error',   ms || 6000); },
		info:    function (msg, ms) { return this.show(msg, 'info',    ms); }
	};

	// ─────────────────────────────────────────────────────────────────────
	// HTTP layer (fetch + XHR per upload con progress)
	// ─────────────────────────────────────────────────────────────────────

	/* NUOVO — il gettone del canale dell app, gia' nella pagina (window.RDACADEMY.nonce). */
	function appNonce() {
		return (global.RDACADEMY && global.RDACADEMY.nonce) ? String(global.RDACADEMY.nonce) : '';
	}

	function mapHttpError(status, body) {
		// body può essere {ok:false, error:'...'} dal REST 1B.2
		var apiErr = body && body.error ? String(body.error) : '';
		if (status === 401) return t('err_unauthenticated');
		if (status === 403) return t('err_forbidden');
		if (status === 400 && apiErr === 'entity_master_mismatch') return t('err_mismatch');
		if (status === 413) return t('err_too_large_server');
		if (status === 0)   return t('err_network');
		if (status >= 500)  return t('err_generic');
		// Per altri 4xx con messaggio API, mostriamo il messaggio breve API se presente.
		if (body && body.message) return body.message;
		return t('err_generic');
	}

	/**
	 * Upload via XMLHttpRequest per avere progress events.
	 * fetch() non espone upload progress in modo standard senza ReadableStream.
	 * Restituisce Promise<{ok, body, status}>.
	 */
	function xhrUpload(url, formData, nonce, onProgress) {
		return new Promise(function (resolve) {
			var xhr = new XMLHttpRequest();
			xhr.open('POST', url, true);
			if (nonce) xhr.setRequestHeader('X-WP-Nonce', nonce);
			xhr.setRequestHeader('X-RDACADEMY-Nonce', appNonce());
			xhr.upload.onprogress = function (e) {
				if (e.lengthComputable && typeof onProgress === 'function') {
					onProgress(Math.round((e.loaded / e.total) * 100));
				}
			};
			xhr.onload = function () {
				var body = null;
				try { body = JSON.parse(xhr.responseText); } catch (_) { /* non-JSON */ }
				resolve({ ok: xhr.status >= 200 && xhr.status < 300, status: xhr.status, body: body });
			};
			xhr.onerror = function () {
				resolve({ ok: false, status: 0, body: null });
			};
			xhr.send(formData);
		});
	}

	/**
	 * GET JSON via fetch.
	 */
	function fetchJSON(url, nonce) {
		return fetch(url, {
			credentials: 'same-origin',
			headers: nonce ? { 'X-WP-Nonce': nonce } : {}
		}).then(function (r) {
			return r.json().catch(function () { return null; })
				.then(function (body) {
					return { ok: r.ok, status: r.status, body: body };
				});
		}).catch(function () {
			return { ok: false, status: 0, body: null };
		});
	}

	/**
	 * POST JSON via fetch.
	 */
	function postJSON(url, nonce) {
		var h = { 'X-RDACADEMY-Nonce': appNonce() };
		if (nonce) h['X-WP-Nonce'] = nonce;
		return fetch(url, {
			method: 'POST',
			credentials: 'same-origin',
			headers: h
		}).then(function (r) {
			return r.json().catch(function () { return null; })
				.then(function (body) {
					return { ok: r.ok, status: r.status, body: body };
				});
		}).catch(function () {
			return { ok: false, status: 0, body: null };
		});
	}

	// ─────────────────────────────────────────────────────────────────────
	// DropZone
	// ─────────────────────────────────────────────────────────────────────

	function DropZone(options) {
		if (!(this instanceof DropZone)) return new DropZone(options);
		this.opts = options || {};
		this._mounted = false;
		this._dom = {};
		this._dragCounter = 0; // robustness per dragenter/dragleave nidificati
		this._queue = [];
		this._activeCount = 0;

		// Required guards
		if (!this.opts.containerEl) throw new Error('RdacademyUploads.DropZone: containerEl mancante');
		if (!this.opts.entityType)  throw new Error('RdacademyUploads.DropZone: entityType mancante');
		if (!this.opts.entityId)    throw new Error('RdacademyUploads.DropZone: entityId mancante');
		// TAGLIO: entityMasterId (UUID dei record del CRM): il motore del guscio verifica l oggetto per id.
		if (!this.opts.scope)       throw new Error('RdacademyUploads.DropZone: scope mancante');

		this.opts.multiple = this.opts.multiple !== false; // default true
		this.opts.maxConcurrent = Number(this.opts.maxConcurrent) || getConfig().maxConcurrent || 3;
	}

	DropZone.prototype.mount = function () {
		if (this._mounted) return this;
		var self = this;

		var dz = el('button', {
			'class': 'rdacademy-uploads__dropzone',
			'type': 'button',
			'role': 'button',
			'aria-label': t('dropzone_aria'),
			'aria-describedby': null,
			'tabindex': '0'
		}, [
			svgIcon('upload', 'rdacademy-uploads__dropzone-icon'),
			el('p', { 'class': 'rdacademy-uploads__dropzone-label', 'text': t('dropzone_label') }),
			el('p', { 'class': 'rdacademy-uploads__dropzone-or', 'text': t('dropzone_or') }),
			el('span', { 'class': 'rdacademy-uploads__dropzone-select', 'text': t('dropzone_select') }),
			el('p', { 'class': 'rdacademy-uploads__dropzone-help', 'text': t('dropzone_help') })
		]);

		var input = el('input', {
			'class': 'rdacademy-uploads__dropzone-input',
			'type': 'file',
			'tabindex': '-1',
			'aria-hidden': 'true',
			'multiple': this.opts.multiple ? true : null,
			'accept': getConfig().allowedExtensions.map(function (e) { return '.' + e; }).join(',')
		});

		// Banner placeholder (mostrato/nascosto da Banner helper)
		var bannerSlot = el('div', { 'class': 'rdacademy-uploads__banner-slot' });

		// Coda upload in corso
		var queueRegion = el('div', {
			'class': 'rdacademy-uploads__queue',
			'role': 'region',
			'aria-live': 'polite',
			'aria-label': 'Upload in corso'
		});

		var wrap = el('div', { 'class': 'rdacademy-uploads' }, [dz, input, bannerSlot, queueRegion]);

		this._dom.wrap = wrap;
		this._dom.dropzone = dz;
		this._dom.input = input;
		this._dom.bannerSlot = bannerSlot;
		this._dom.queue = queueRegion;

		// ─ Event listeners ─

		// Click → trigger file picker
		this._onDzClick = function () { input.click(); };
		dz.addEventListener('click', this._onDzClick);

		// Keyboard Enter/Space → file picker (button gestisce Enter nativamente,
		// ma esplicitiamo Space per copertura)
		this._onDzKeydown = function (e) {
			if (e.key === ' ' || e.key === 'Spacebar') {
				e.preventDefault();
				input.click();
			}
		};
		dz.addEventListener('keydown', this._onDzKeydown);

		// File selection via picker
		this._onInputChange = function (e) {
			self._handleFiles(e.target.files);
			input.value = ''; // reset per ri-selezione stesso file
		};
		input.addEventListener('change', this._onInputChange);

		// Drag & drop
		this._onDragEnter = function (e) {
			e.preventDefault();
			self._dragCounter++;
			dz.classList.add('rdacademy-uploads__dropzone--dragover');
			var label = dz.querySelector('.rdacademy-uploads__dropzone-label');
			if (label) label.textContent = t('dropzone_drop_active');
		};
		this._onDragOver = function (e) { e.preventDefault(); };
		this._onDragLeave = function (e) {
			e.preventDefault();
			self._dragCounter = Math.max(0, self._dragCounter - 1);
			if (self._dragCounter === 0) {
				dz.classList.remove('rdacademy-uploads__dropzone--dragover');
				var label = dz.querySelector('.rdacademy-uploads__dropzone-label');
				if (label) label.textContent = t('dropzone_label');
			}
		};
		this._onDrop = function (e) {
			e.preventDefault();
			self._dragCounter = 0;
			dz.classList.remove('rdacademy-uploads__dropzone--dragover');
			var label = dz.querySelector('.rdacademy-uploads__dropzone-label');
			if (label) label.textContent = t('dropzone_label');
			if (e.dataTransfer && e.dataTransfer.files) {
				self._handleFiles(e.dataTransfer.files);
			}
		};

		dz.addEventListener('dragenter', this._onDragEnter);
		dz.addEventListener('dragover',  this._onDragOver);
		dz.addEventListener('dragleave', this._onDragLeave);
		dz.addEventListener('drop',      this._onDrop);

		// Mount finale
		this.opts.containerEl.appendChild(wrap);
		this._mounted = true;
		return this;
	};

	DropZone.prototype._handleFiles = function (fileList) {
		var self = this;
		if (!fileList || !fileList.length) return;

		// Push in coda, validazione client immediata.
		Array.prototype.forEach.call(fileList, function (file) {
			var err = validateFile(file);
			if (err) {
				Toast.error(t(err) + ': ' + file.name);
				if (typeof self.opts.onError === 'function') {
					self.opts.onError({ stage: 'validate', code: err, message: t(err) }, file);
				}
				return;
			}
			self._queue.push(file);
		});

		this._pump();
	};

	DropZone.prototype._pump = function () {
		while (this._activeCount < this.opts.maxConcurrent && this._queue.length > 0) {
			var file = this._queue.shift();
			this._uploadOne(file);
		}
		if (this._activeCount === 0 && this._queue.length === 0) {
			if (typeof this.opts.onAllComplete === 'function') {
				this.opts.onAllComplete();
			}
		}
	};

	DropZone.prototype._uploadOne = function (file) {
		var self = this;
		var cfg = getConfig();
		this._activeCount++;

		// UI riga in coda
		var fillBar = el('span', { 'class': 'rdacademy-uploads__queue-bar-fill' });
		var percentLabel = el('span', { 'class': 'rdacademy-uploads__queue-percent', 'text': '0%' });
		var queueItem = el('div', { 'class': 'rdacademy-uploads__queue-item' }, [
			svgIcon('file', 'rdacademy-uploads__queue-icon'),
			el('div', { 'class': 'rdacademy-uploads__queue-body' }, [
				el('span', { 'class': 'rdacademy-uploads__queue-filename', 'text': file.name }),
				el('span', { 'class': 'rdacademy-uploads__queue-bar', 'role': 'progressbar', 'aria-valuemin': '0', 'aria-valuemax': '100', 'aria-valuenow': '0' }, [fillBar])
			]),
			percentLabel
		]);
		this._dom.queue.appendChild(queueItem);

		var formData = new FormData();
		formData.append('entity_type', this.opts.entityType);
		formData.append('entity_id', String(this.opts.entityId));
		formData.append('scope', this.opts.scope);
		formData.append('file', file);

		xhrUpload(
			cfg.apiBase + '/attach',
			formData,
			cfg.nonce,
			function (pct) {
				fillBar.style.width = pct + '%';
				percentLabel.textContent = pct + '%';
				queueItem.querySelector('.rdacademy-uploads__queue-bar').setAttribute('aria-valuenow', String(pct));
			}
		).then(function (resp) {
			self._activeCount--;

			if (resp.ok && resp.body && resp.body.ok) {
				queueItem.classList.add('rdacademy-uploads__queue-item--success');
				fillBar.style.width = '100%';
				percentLabel.textContent = '100%';

				Toast.success(t('upload_success', { filename: file.name }));

				// Banner duplicato se blob già esistente
				if (resp.body.blob_was_new === false && self._dom.bannerSlot) {
					Banner.showDuplicate({
						containerEl: self._dom.bannerSlot,
						filename: file.name
					});
				}

				if (typeof self.opts.onSuccess === 'function') {
					self.opts.onSuccess(resp.body, file);
				}

				// Rimuovi riga coda dopo 1.5s (UX feedback prima di pulizia)
				setTimeout(function () {
					if (queueItem.parentNode) queueItem.parentNode.removeChild(queueItem);
				}, 1500);
			} else {
				queueItem.classList.add('rdacademy-uploads__queue-item--error');
				fillBar.style.width = '100%';
				var msg = mapHttpError(resp.status, resp.body);
				percentLabel.textContent = '✕';
				Toast.error(file.name + ': ' + msg);
				if (typeof self.opts.onError === 'function') {
					self.opts.onError({ stage: 'upload', status: resp.status, message: msg, body: resp.body }, file);
				}
				setTimeout(function () {
					if (queueItem.parentNode) queueItem.parentNode.removeChild(queueItem);
				}, 5000);
			}

			self._pump();
		});
	};

	DropZone.prototype.unmount = function () {
		if (!this._mounted) return;
		var d = this._dom;

		if (d.dropzone) {
			d.dropzone.removeEventListener('click', this._onDzClick);
			d.dropzone.removeEventListener('keydown', this._onDzKeydown);
			d.dropzone.removeEventListener('dragenter', this._onDragEnter);
			d.dropzone.removeEventListener('dragover',  this._onDragOver);
			d.dropzone.removeEventListener('dragleave', this._onDragLeave);
			d.dropzone.removeEventListener('drop',      this._onDrop);
		}
		if (d.input) d.input.removeEventListener('change', this._onInputChange);

		if (d.wrap && d.wrap.parentNode) d.wrap.parentNode.removeChild(d.wrap);
		this._dom = {};
		this._mounted = false;
	};

	DropZone.prototype.reset = function () {
		this._queue = [];
		this._activeCount = 0;
		this._dragCounter = 0;
		if (this._dom.queue) this._dom.queue.innerHTML = '';
		if (this._dom.bannerSlot) this._dom.bannerSlot.innerHTML = '';
	};

	DropZone.prototype.setDisabled = function (flag) {
		if (!this._dom.dropzone) return;
		this._dom.dropzone.setAttribute('aria-disabled', flag ? 'true' : 'false');
		this._dom.dropzone.setAttribute('tabindex', flag ? '-1' : '0');
	};

	// ─────────────────────────────────────────────────────────────────────
	// List
	// ─────────────────────────────────────────────────────────────────────

	function RdacademyList(options) {
		if (!(this instanceof RdacademyList)) return new RdacademyList(options);
		this.opts = options || {};
		this._mounted = false;
		this._dom = {};

		if (!this.opts.containerEl) throw new Error('RdacademyUploads.List: containerEl mancante');
		if (!this.opts.entityType)  throw new Error('RdacademyUploads.List: entityType mancante');
		if (!this.opts.entityId)    throw new Error('RdacademyUploads.List: entityId mancante');

		this.opts.includeDeleted = !!this.opts.includeDeleted;
	}

	RdacademyList.prototype.mount = function () {
		if (this._mounted) return this;
		var self = this;

		var refreshBtn = el('button', {
			'class': 'rdacademy-uploads__list-refresh',
			'type': 'button',
			'aria-label': t('list_refresh'),
			'title': t('list_refresh')
		}, [svgIcon('refresh', '')]);
		refreshBtn.addEventListener('click', function () { self.refresh(); });

		var countLabel = el('span', { 'class': 'rdacademy-uploads__list-count', 'text': '' });

		var header = el('div', { 'class': 'rdacademy-uploads__list-header' }, [
			el('div', null, [
				el('h3', { 'class': 'rdacademy-uploads__list-title', 'text': t('list_title') }),
				countLabel
			]),
			refreshBtn
		]);

		var body = el('div', { 'class': 'rdacademy-uploads__list-body', 'aria-live': 'polite' });

		var wrap = el('div', { 'class': 'rdacademy-uploads' }, [
			el('div', { 'class': 'rdacademy-uploads__list' }, [header, body])
		]);

		this._dom = { wrap: wrap, body: body, countLabel: countLabel };
		this.opts.containerEl.appendChild(wrap);
		this._mounted = true;
		this.refresh();
		return this;
	};

	RdacademyList.prototype.refresh = function () {
		var self = this;
		var cfg = getConfig();
		if (!this._mounted) return;

		this._dom.body.innerHTML = '';
		this._dom.body.appendChild(el('div', { 'class': 'rdacademy-uploads__list-loading', 'text': t('list_loading') }));

		var url = cfg.apiBase + '/list'
			+ (cfg.apiBase.indexOf('?') >= 0 ? '&' : '?')
			+ 'entity_type=' + encodeURIComponent(this.opts.entityType)
			+ '&entity_id='   + encodeURIComponent(String(this.opts.entityId))
			+ (this.opts.scope ? '&scope=' + encodeURIComponent(this.opts.scope) : '')
			+ (this.opts.includeDeleted ? '&include_deleted=1' : '');

		fetchJSON(url, cfg.nonce).then(function (resp) {
			if (!self._mounted) return;
			self._dom.body.innerHTML = '';
			if (!resp.ok || !resp.body || !resp.body.ok) {
				// RP-ERRORE-NON-SI-TRAVESTE-DA-DATO: un elenco non letto non e' un elenco vuoto.
				var msg = mapHttpError(resp.status, resp.body);
				self._dom.body.appendChild(el('div', { 'class': 'rdacademy-uploads__list-loading rdacademy-uploads__list-error', 'role': 'alert', 'text': msg }));
				self._dom.countLabel.textContent = '';
				return;
			}
			self._renderItems(resp.body.uploads || []);
		});
	};

	RdacademyList.prototype._renderItems = function (items) {
		var self = this;
		this._items = items;
		var n = items.length;
		if (n === 0) {
			this._dom.countLabel.textContent = '';
			this._renderEmpty();
			return;
		}
		this._dom.countLabel.textContent = n === 1 ? t('list_count_one') : t('list_count', { count: n });
		var ul = el('ul', { 'class': 'rdacademy-uploads__list-items' });
		items.forEach(function (item) { ul.appendChild(self._renderItem(item)); });
		this._dom.body.appendChild(ul);
	};

	RdacademyList.prototype._renderEmpty = function () {
		this._dom.body.appendChild(el('div', { 'class': 'rdacademy-uploads__list-empty' }, [
			svgIcon('emptyBox', 'rdacademy-uploads__list-empty-icon'),
			el('p', { 'class': 'rdacademy-uploads__list-empty-title', 'text': t('list_empty_title') }),
			el('p', { 'class': 'rdacademy-uploads__list-empty-hint',  'text': t('list_empty_hint') })
		]));
	};

	RdacademyList.prototype._renderItem = function (item) {
		var self = this;
		var cfg = getConfig();
		var isDeleted = !!item.deleted_at;
		var filename = item.filename_original || '';
		var downloadHref = item.download_url || (cfg.apiBase + '/' + item.id + '/download');

		var meta = el('div', { 'class': 'rdacademy-uploads__list-item-meta' }, [
			el('span', { 'text': formatSize(item.size_bytes) }),
			el('span', { 'text': t('list_item_date', { date: formatDate(item.uploaded_at) }) })
		]);
		if (isDeleted) {
			meta.appendChild(el('span', { 'class': 'rdacademy-uploads__list-item-badge', 'text': t('list_item_deleted') }));
		}

		var dlA = el('a', {
			'class': 'rdacademy-uploads__list-item-action rdacademy-uploads__list-item-action--download',
			'href': isDeleted ? null : downloadHref,
			'aria-label': t('list_item_download', { filename: filename }),
			'title': t('list_item_download', { filename: filename }),
			'rel': 'noopener'
		}, [svgIcon('download', '')]);

		var delBtn = el('button', {
			'class': 'rdacademy-uploads__list-item-action rdacademy-uploads__list-item-action--delete',
			'type': 'button',
			'aria-label': t('list_item_delete', { filename: filename }),
			'title': isDeleted ? t('list_item_deleted') : t('list_item_delete', { filename: filename }),
			'disabled': isDeleted ? true : null
		}, [svgIcon('trash', '')]);

		var actions = el('div', { 'class': 'rdacademy-uploads__list-item-actions' }, [dlA, delBtn]);
		var node = el('li', {
			'class': 'rdacademy-uploads__list-item' + (isDeleted ? ' rdacademy-uploads__list-item--deleted' : ''),
			'data-upload-id': String(item.id)
		}, [
			el('span', { 'class': 'rdacademy-uploads__list-item-icon' }, [svgIcon('file', '')]),
			el('div', { 'class': 'rdacademy-uploads__list-item-body' }, [
				el('span', { 'class': 'rdacademy-uploads__list-item-filename', 'text': filename, 'title': filename }),
				meta
			]),
			actions
		]);
		if (!isDeleted) {
			delBtn.addEventListener('click', function () { self._askDelete(item, node, actions); });
		}
		return node;
	};

	/* NUOVO — la conferma DENTRO la riga (al posto di window.confirm). */
	RdacademyList.prototype._askDelete = function (item, node, actions) {
		var self = this;
		if (node.classList.contains('is-asking')) return;
		node.classList.add('is-asking');
		actions.hidden = true;
		var yes = el('button', { 'class': 'rdacademy-uploads__ask-yes', 'type': 'button', 'text': t('confirm_yes') });
		var no  = el('button', { 'class': 'rdacademy-uploads__ask-no', 'type': 'button', 'text': t('confirm_no') });
		var ask = el('div', { 'class': 'rdacademy-uploads__ask', 'role': 'group' }, [
			el('span', { 'class': 'rdacademy-uploads__ask-text', 'text': t('confirm_delete', { filename: item.filename_original }) }), yes, no
		]);
		node.appendChild(ask);
		function chiudi() { if (ask.parentNode) ask.parentNode.removeChild(ask); actions.hidden = false; node.classList.remove('is-asking'); }
		no.addEventListener('click', chiudi);
		yes.addEventListener('click', function () {
			yes.disabled = true; no.disabled = true;
			self._doDelete(item, chiudi);
		});
		no.focus();
	};

	RdacademyList.prototype._doDelete = function (item, chiudi) {
		var self = this;
		var cfg = getConfig();
		postJSON(cfg.apiBase + '/' + item.id + '/delete', cfg.nonce).then(function (resp) {
			if (resp.ok && resp.body && resp.body.ok) {
				Toast.success(t('list_item_deleted') + ': ' + item.filename_original);
				if (typeof self.opts.onItemDelete === 'function') self.opts.onItemDelete(item, resp.body);
				self.refresh();
			} else {
				chiudi();
				Toast.error(mapHttpError(resp.status, resp.body));
			}
		});
	};

	RdacademyList.prototype.unmount = function () {
		if (!this._mounted) return;
		if (this._dom.wrap && this._dom.wrap.parentNode) {
			this._dom.wrap.parentNode.removeChild(this._dom.wrap);
		}
		this._dom = {};
		this._mounted = false;
	};

	// ─────────────────────────────────────────────────────────────────────
	// Banner duplicato v1 (informativo, §21bis.15.9)
	// ─────────────────────────────────────────────────────────────────────

	var Banner = {
		_currentNode: null,
		_currentTimer: null,

		showDuplicate: function (opts) {
			opts = opts || {};
			if (!opts.containerEl) return;
			var filename = opts.filename || '';
			var autoHide = typeof opts.autoHide === 'number' ? opts.autoHide : 8000;

			// Sostituisce eventuale banner precedente nello stesso slot.
			this.hide();

			var text = t('duplicate_banner', { filename: filename });
			// Bolda il filename nella stringa: ricerchiamo l'esatto match e sostituiamo.
			var html;
			if (filename && text.indexOf(filename) >= 0) {
				var escFn = filename.replace(/[<>&"]/g, function (c) {
					return ({ '<':'&lt;', '>':'&gt;', '&':'&amp;', '"':'&quot;' })[c];
				});
				var escText = text.replace(/[<>&"]/g, function (c) {
					return ({ '<':'&lt;', '>':'&gt;', '&':'&amp;', '"':'&quot;' })[c];
				});
				html = escText.split(escFn).join('<strong>' + escFn + '</strong>');
			} else {
				html = text.replace(/[<>&"]/g, function (c) {
					return ({ '<':'&lt;', '>':'&gt;', '&':'&amp;', '"':'&quot;' })[c];
				});
			}

			var closeBtn = el('button', {
				'class': 'rdacademy-uploads__banner-close',
				'type': 'button',
				'aria-label': t('duplicate_banner_close'),
				'title': t('duplicate_banner_close')
			}, [svgIcon('close', '')]);

			var banner = el('div', {
				'class': 'rdacademy-uploads__banner',
				'role': 'status',
				'aria-live': 'polite',
				'aria-label': t('duplicate_banner_aria')
			}, [
				svgIcon('info', 'rdacademy-uploads__banner-icon'),
				el('p', { 'class': 'rdacademy-uploads__banner-text', 'html': html }),
				closeBtn
			]);

			var self = this;
			closeBtn.addEventListener('click', function () { self.hide(); });

			opts.containerEl.appendChild(banner);
			this._currentNode = banner;

			if (autoHide > 0) {
				this._currentTimer = setTimeout(function () { self.hide(); }, autoHide);
			}

			return banner;
		},

		hide: function () {
			if (this._currentTimer) {
				clearTimeout(this._currentTimer);
				this._currentTimer = null;
			}
			if (this._currentNode && this._currentNode.parentNode) {
				this._currentNode.parentNode.removeChild(this._currentNode);
			}
			this._currentNode = null;
		}
	};

	// ─────────────────────────────────────────────────────────────────────
	// Esposizione globale (copiata)
	// ─────────────────────────────────────────────────────────────────────

	var existingConfig = (global.RdacademyUploads && global.RdacademyUploads.config) || null;

	global.RdacademyUploads = global.RdacademyUploads || {};
	global.RdacademyUploads.DropZone = DropZone;
	global.RdacademyUploads.List     = RdacademyList;
	global.RdacademyUploads.Banner   = Banner;
	global.RdacademyUploads.Toast    = Toast;
	if (existingConfig && !global.RdacademyUploads.config) {
		global.RdacademyUploads.config = existingConfig;
	}

})(typeof window !== 'undefined' ? window : this);
