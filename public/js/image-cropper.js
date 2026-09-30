/**
 * Pentapure Image Cropper Modal
 * Mobile-friendly touch-enabled image crop & adjust utility using Cropper.js.
 * Supports camera capture, gallery upload, rotation, zoom, freeform/aspect ratio cropping.
 */
(function() {
  'use strict';

  // Ensure dependencies (Cropper.js) are loaded
  function loadCropperDeps(callback) {
    if (window.Cropper) {
      return callback();
    }

    // Load CSS
    if (!document.getElementById('cropper-css')) {
      const link = document.createElement('link');
      link.id = 'cropper-css';
      link.rel = 'stylesheet';
      link.href = (window.baseUrl ? window.baseUrl + '/vendor/cropperjs/cropper.min.css' : '/vendor/cropperjs/cropper.min.css');
      link.onerror = function() {
        this.href = 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css';
      };
      document.head.appendChild(link);
    }

    // Load JS
    if (!document.getElementById('cropper-js')) {
      const script = document.createElement('script');
      script.id = 'cropper-js';
      script.src = (window.baseUrl ? window.baseUrl + '/vendor/cropperjs/cropper.min.js' : '/vendor/cropperjs/cropper.min.js');
      script.onload = () => callback();
      script.onerror = function() {
        const fallback = document.createElement('script');
        fallback.src = 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js';
        fallback.onload = () => callback();
        document.head.appendChild(fallback);
      };
      document.head.appendChild(script);
    } else {
      const existing = document.getElementById('cropper-js');
      existing.addEventListener('load', () => callback());
    }
  }

  // Inject Modal Styles
  function injectStyles() {
    if (document.getElementById('image-cropper-styles')) return;

    const style = document.createElement('style');
    style.id = 'image-cropper-styles';
    style.textContent = `
      .crop-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        width: 100vw;
        height: 100vh;
        height: 100dvh;
        background: rgba(10, 15, 29, 0.92);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        z-index: 9999999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 12px;
        box-sizing: border-box;
        animation: cropFadeIn 0.2s ease-out;
      }
      @keyframes cropFadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
      }
      .crop-modal-dialog {
        background: #1e293b;
        color: #ffffff;
        border: 1px solid rgba(255, 255, 255, 0.18);
        border-radius: 14px;
        width: 100%;
        max-width: 760px;
        height: 90vh;
        max-height: 820px;
        display: flex;
        flex-direction: column;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
        overflow: hidden;
      }
      .crop-modal-dialog,
      .crop-modal-dialog * {
        box-sizing: border-box;
      }
      .crop-modal-header {
        flex: 0 0 auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem 1.1rem;
        background: #0b1120;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      }
      .crop-modal-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #f8fafc !important;
        -webkit-text-fill-color: #f8fafc !important;
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
        letter-spacing: 0.3px;
      }
      .crop-modal-title * {
        color: #f8fafc !important;
        -webkit-text-fill-color: #f8fafc !important;
      }
      .crop-modal-close {
        background: transparent !important;
        border: none !important;
        color: #94a3b8 !important;
        -webkit-text-fill-color: #94a3b8 !important;
        font-size: 1.6rem !important;
        line-height: 1 !important;
        cursor: pointer !important;
        padding: 4px 8px !important;
        border-radius: 6px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        transition: all 0.15s ease !important;
      }
      .crop-modal-close:hover {
        color: #ffffff !important;
        -webkit-text-fill-color: #ffffff !important;
        background: rgba(255, 255, 255, 0.12) !important;
      }
      .crop-modal-body {
        flex: 1 1 auto;
        position: relative;
        background: #020617;
        width: 100%;
        min-height: 0;
        height: 100%;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
      }
      .crop-modal-body img {
        display: block;
        max-width: 100%;
        max-height: 100%;
      }
      .crop-modal-toolbar {
        flex: 0 0 auto;
        padding: 0.5rem 0.8rem;
        background: #0f172a;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        display: flex;
        gap: 6px;
        align-items: center;
        justify-content: flex-start;
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
      }
      @media (min-width: 641px) {
        .crop-modal-toolbar {
          justify-content: center;
          gap: 8px;
          padding: 0.65rem 1rem;
        }
      }
      .crop-tool-btn {
        background: #1e293b !important;
        color: #ffffff !important;
        -webkit-text-fill-color: #ffffff !important;
        border: 1px solid rgba(255, 255, 255, 0.22) !important;
        padding: 6px 11px !important;
        border-radius: 6px !important;
        font-size: 0.82rem !important;
        font-weight: 600 !important;
        cursor: pointer !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 5px !important;
        user-select: none !important;
        white-space: nowrap !important;
        flex-shrink: 0 !important;
        transition: all 0.15s ease !important;
      }
      .crop-tool-btn * {
        color: #ffffff !important;
        -webkit-text-fill-color: #ffffff !important;
      }
      .crop-tool-btn svg {
        flex-shrink: 0;
      }
      .crop-tool-btn:hover {
        background: #334155 !important;
        border-color: rgba(255, 255, 255, 0.4) !important;
      }
      .crop-tool-btn.active {
        background: #f59e0b !important;
        color: #000000 !important;
        -webkit-text-fill-color: #000000 !important;
        border-color: #f59e0b !important;
        font-weight: 700 !important;
        box-shadow: 0 0 8px rgba(245, 158, 11, 0.4) !important;
      }
      .crop-tool-btn.active * {
        color: #000000 !important;
        -webkit-text-fill-color: #000000 !important;
      }
      .crop-modal-footer {
        flex: 0 0 auto;
        padding: 0.75rem 1.1rem;
        padding-bottom: max(0.75rem, env(safe-area-inset-bottom));
        background: #0b1120;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
      }
      .crop-modal-footer-hint {
        color: #94a3b8 !important;
        -webkit-text-fill-color: #94a3b8 !important;
        font-size: 0.8rem;
        font-weight: 500;
        display: inline-block;
      }
      .crop-modal-footer-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-left: auto;
      }
      .crop-btn-cancel {
        background: #334155 !important;
        color: #ffffff !important;
        -webkit-text-fill-color: #ffffff !important;
        border: 1px solid rgba(255, 255, 255, 0.22) !important;
        padding: 0.6rem 1.2rem !important;
        border-radius: 8px !important;
        font-size: 0.9rem !important;
        font-weight: 600 !important;
        cursor: pointer !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        transition: background 0.15s ease !important;
      }
      .crop-btn-cancel * {
        color: #ffffff !important;
        -webkit-text-fill-color: #ffffff !important;
      }
      .crop-btn-cancel:hover {
        background: #475569 !important;
      }
      .crop-btn-done {
        background: #f59e0b !important;
        color: #000000 !important;
        -webkit-text-fill-color: #000000 !important;
        border: 1px solid #d97706 !important;
        padding: 0.6rem 1.4rem !important;
        border-radius: 8px !important;
        font-size: 0.92rem !important;
        font-weight: 700 !important;
        cursor: pointer !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 6px !important;
        box-shadow: 0 4px 6px -1px rgba(245, 158, 11, 0.3) !important;
        transition: all 0.15s ease !important;
      }
      .crop-btn-done * {
        color: #000000 !important;
        -webkit-text-fill-color: #000000 !important;
      }
      .crop-btn-done:hover {
        background: #fbbf24 !important;
        transform: translateY(-1px);
      }
      /* Mobile Viewport */
      @media (max-width: 640px) {
        .crop-modal-overlay {
          padding: 0 !important;
        }
        .crop-modal-dialog {
          width: 100vw !important;
          height: 100dvh !important;
          height: 100vh !important;
          max-height: 100dvh !important;
          border-radius: 0 !important;
          border: none !important;
        }
        .crop-modal-footer-hint {
          display: none !important;
        }
        .crop-modal-footer-actions {
          width: 100% !important;
          margin-left: 0 !important;
          gap: 8px !important;
        }
        .crop-btn-cancel {
          flex: 1 1 40% !important;
          padding: 0.75rem 0.8rem !important;
          font-size: 0.9rem !important;
        }
        .crop-btn-done {
          flex: 1 1 60% !important;
          padding: 0.75rem 0.8rem !important;
          font-size: 0.95rem !important;
        }
        .crop-tool-btn {
          padding: 6px 9px !important;
          font-size: 0.78rem !important;
        }
      }

      /* Unified Camera and Gallery Buttons (Cashier / Dispatch / Everywhere) */
      .btn-media-camera,
      .btn-bill-camera {
        background: #f59e0b !important;
        color: #1e293b !important;
        -webkit-text-fill-color: #1e293b !important;
        border: 1.5px solid #d97706 !important;
        font-weight: 700 !important;
        border-radius: 8px !important;
        padding: 0.55rem 0.95rem !important;
        font-size: 0.85rem !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 6px !important;
        box-shadow: 0 2px 5px rgba(245, 158, 11, 0.25) !important;
        cursor: pointer !important;
        transition: all 0.15s ease !important;
        white-space: nowrap !important;
        user-select: none !important;
      }
      .btn-media-camera:hover,
      .btn-bill-camera:hover {
        background: #fbbf24 !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(245, 158, 11, 0.35) !important;
      }
      .btn-media-camera *,
      .btn-bill-camera * {
        color: #1e293b !important;
        -webkit-text-fill-color: #1e293b !important;
      }

      .btn-media-gallery,
      .btn-bill-gallery {
        background: #ffffff !important;
        color: #1e293b !important;
        -webkit-text-fill-color: #1e293b !important;
        border: 1.5px solid #cbd5e1 !important;
        font-weight: 700 !important;
        border-radius: 8px !important;
        padding: 0.55rem 0.95rem !important;
        font-size: 0.85rem !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 6px !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08) !important;
        cursor: pointer !important;
        transition: all 0.15s ease !important;
        white-space: nowrap !important;
        user-select: none !important;
      }
      .btn-media-gallery:hover,
      .btn-bill-gallery:hover {
        background: #f1f5f9 !important;
        border-color: #94a3b8 !important;
        transform: translateY(-1px);
      }
      .btn-media-gallery *,
      .btn-bill-gallery * {
        color: #1e293b !important;
        -webkit-text-fill-color: #1e293b !important;
      }

      .bill-file-actions {
        display: none;
        align-items: center;
        gap: 8px;
        font-size: 0.82rem;
        font-weight: 600;
        padding: 5px 10px;
        background: #f8fafc !important;
        border-radius: 8px;
        border: 1.5px solid #cbd5e1 !important;
        max-width: 100%;
        box-sizing: border-box;
      }
      .bill-file-actions * {
        color: #1e293b !important;
        -webkit-text-fill-color: #1e293b !important;
      }

      @media (max-width: 600px) {
        .bill-attach-container {
          width: 100% !important;
          flex: 1 1 100% !important;
        }
        .bill-attach-container > div {
          display: flex !important;
          width: 100% !important;
          gap: 8px !important;
        }
        .btn-bill-camera,
        .btn-bill-gallery,
        .btn-media-camera,
        .btn-media-gallery {
          flex: 1 1 calc(50% - 4px) !important;
          width: calc(50% - 4px) !important;
          padding: 0.65rem 0.4rem !important;
          font-size: 0.8rem !important;
          box-sizing: border-box !important;
        }
      }
    `;
    document.head.appendChild(style);
  }

  // Auto inject styles on load
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', injectStyles);
  } else {
    injectStyles();
  }

  const ImageCropper = {
    cropperInstance: null,
    currentModal: null,

    /**
     * Open the crop modal with a file, blob, or URL.
     * @param {Object} options
     *   - file: File | Blob | string (url/dataUrl)
     *   - title: string (optional)
     *   - originalName: string (optional)
     *   - aspectRatio: number | NaN (default NaN - freeform)
     *   - onDone: function({ file, blob, dataUrl })
     *   - onCancel: function()
     */
    open: function(options) {
      injectStyles();
      loadCropperDeps(() => {
        ImageCropper._initModal(options);
      });
    },

    _initModal: function(options) {
      if (this.currentModal) {
        this.close();
      }

      const title = options.title || '✂️ Crop & Adjust Photo';
      const originalFile = options.file;
      const fileName = options.originalName || (originalFile && originalFile.name ? originalFile.name : 'photo_' + Date.now() + '.jpg');

      // Create modal elements
      const overlay = document.createElement('div');
      overlay.className = 'crop-modal-overlay';

      overlay.innerHTML = `
        <div class="crop-modal-dialog" role="dialog" aria-modal="true">
          <div class="crop-modal-header">
            <h3 class="crop-modal-title">${title}</h3>
            <button type="button" class="crop-modal-close" title="Close">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
          </div>
          <div class="crop-modal-body">
            <img id="image-cropper-target" src="" alt="To Crop">
          </div>
          <div class="crop-modal-toolbar">
            <button type="button" class="crop-tool-btn" data-action="rotate-left" title="Rotate 90° Left">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path><path d="M3 3v5h5"></path></svg>
              <span>Rotate L</span>
            </button>
            <button type="button" class="crop-tool-btn" data-action="rotate-right" title="Rotate 90° Right">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"></path><path d="M21 3v5h-5"></path></svg>
              <span>Rotate R</span>
            </button>
            <button type="button" class="crop-tool-btn" data-action="zoom-in" title="Zoom In">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line><line x1="11" y1="8" x2="11" y2="14"></line><line x1="8" y1="11" x2="14" y2="11"></line></svg>
              <span>Zoom +</span>
            </button>
            <button type="button" class="crop-tool-btn" data-action="zoom-out" title="Zoom Out">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line><line x1="8" y1="11" x2="14" y2="11"></line></svg>
              <span>Zoom -</span>
            </button>
            <button type="button" class="crop-tool-btn" data-action="reset" title="Reset Crop">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 0 1 15-6.7L21 8"></path><path d="M21 3v5h-5"></path><path d="M21 12a9 9 0 0 1-15 6.7L3 16"></path><path d="M3 21v-5h5"></path></svg>
              <span>Reset</span>
            </button>
            <button type="button" class="crop-tool-btn active" data-ratio="free" title="Freeform Crop">
              <span>Freeform</span>
            </button>
            <button type="button" class="crop-tool-btn" data-ratio="1" title="1:1 Square">
              <span>1:1</span>
            </button>
          </div>
          <div class="crop-modal-footer">
            <span class="crop-modal-footer-hint">💡 Drag corners/edges to adjust</span>
            <div class="crop-modal-footer-actions">
              <button type="button" class="crop-btn-cancel">Cancel</button>
              <button type="button" class="crop-btn-done">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                <span>Done</span>
              </button>
            </div>
          </div>
        </div>
      `;

      document.body.appendChild(overlay);
      this.currentModal = overlay;
      document.body.style.overflow = 'hidden';

      const targetImg = overlay.querySelector('#image-cropper-target');
      const closeBtn = overlay.querySelector('.crop-modal-close');
      const cancelBtn = overlay.querySelector('.crop-btn-cancel');
      const doneBtn = overlay.querySelector('.crop-btn-done');

      const handleClose = () => {
        this.close();
        if (typeof options.onCancel === 'function') {
          options.onCancel();
        }
      };

      closeBtn.addEventListener('click', handleClose);
      cancelBtn.addEventListener('click', handleClose);

      // Handle Image Source loading
      const initCropper = (srcUrl) => {
        targetImg.src = srcUrl;
        targetImg.onload = () => {
          if (this.cropperInstance) {
            this.cropperInstance.destroy();
          }

          this.cropperInstance = new Cropper(targetImg, {
            viewMode: 1, // Restrict crop box within canvas
            dragMode: 'move',
            aspectRatio: options.aspectRatio !== undefined ? options.aspectRatio : NaN,
            autoCropArea: 0.88,
            restore: false,
            responsive: true,
            guides: true,
            center: true,
            highlight: true,
            cropBoxMovable: true,
            cropBoxResizable: true,
            toggleDragModeOnDblclick: false,
            checkOrientation: true, // Auto handles EXIF orientation from cameras
            rotatable: true,
            scalable: true,
            zoomable: true,
          });

          // Bind Toolbar actions
          overlay.querySelectorAll('.crop-tool-btn').forEach(btn => {
            btn.addEventListener('click', () => {
              if (!this.cropperInstance) return;
              const action = btn.dataset.action;
              const ratio = btn.dataset.ratio;

              if (action === 'rotate-left') this.cropperInstance.rotate(-90);
              else if (action === 'rotate-right') this.cropperInstance.rotate(90);
              else if (action === 'zoom-in') this.cropperInstance.zoom(0.1);
              else if (action === 'zoom-out') this.cropperInstance.zoom(-0.1);
              else if (action === 'reset') this.cropperInstance.reset();
              else if (ratio) {
                overlay.querySelectorAll('[data-ratio]').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                if (ratio === 'free') {
                  this.cropperInstance.setAspectRatio(NaN);
                } else {
                  this.cropperInstance.setAspectRatio(parseFloat(ratio));
                }
              }
            });
          });
        };
      };

      if (typeof originalFile === 'string') {
        initCropper(originalFile);
      } else if (originalFile instanceof Blob || originalFile instanceof File) {
        const reader = new FileReader();
        reader.onload = (e) => initCropper(e.target.result);
        reader.readAsDataURL(originalFile);
      } else {
        console.error('ImageCropper: Invalid file provided to open()');
        handleClose();
        return;
      }

      // Handle Done action
      doneBtn.addEventListener('click', () => {
        if (!this.cropperInstance) return;

        doneBtn.disabled = true;
        doneBtn.innerHTML = `Cropping...`;

        // Generate cropped canvas with high quality
        const canvas = this.cropperInstance.getCroppedCanvas({
          maxWidth: 2048,
          maxHeight: 2048,
          imageSmoothingEnabled: true,
          imageSmoothingQuality: 'high',
        });

        if (!canvas) {
          alert('Could not crop the image.');
          doneBtn.disabled = false;
          doneBtn.innerHTML = 'Done';
          return;
        }

        const dataUrl = canvas.toDataURL('image/jpeg', 0.88);
        canvas.toBlob((blob) => {
          const cleanName = fileName.replace(/\.[^/.]+$/, "") + "_cropped.jpg";
          const croppedFile = new File([blob], cleanName, { type: 'image/jpeg', lastModified: Date.now() });

          const result = {
            blob: blob,
            file: croppedFile,
            dataUrl: dataUrl,
            name: cleanName
          };

          this.close();

          if (typeof options.onDone === 'function') {
            options.onDone(result);
          }
        }, 'image/jpeg', 0.88);
      });
    },

    close: function() {
      if (this.cropperInstance) {
        this.cropperInstance.destroy();
        this.cropperInstance = null;
      }
      if (this.currentModal) {
        this.currentModal.remove();
        this.currentModal = null;
      }
      document.body.style.overflow = '';
    }
  };

  window.ImageCropper = ImageCropper;
})();
