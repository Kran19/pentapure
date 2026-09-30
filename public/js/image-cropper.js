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
        background: rgba(10, 15, 29, 0.88);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        z-index: 999999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 10px;
        box-sizing: border-box;
        animation: cropFadeIn 0.2s ease-out;
      }
      @keyframes cropFadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
      }
      .crop-modal-dialog {
        background: #1e293b;
        color: #f8fafc;
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 14px;
        width: 100%;
        max-width: 720px;
        max-height: 96vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        overflow: hidden;
      }
      .crop-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.8rem 1.2rem;
        background: #0f172a;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      }
      .crop-modal-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #f8fafc;
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
      }
      .crop-modal-close {
        background: none;
        border: none;
        color: #94a3b8;
        font-size: 1.6rem;
        line-height: 1;
        cursor: pointer;
        padding: 0 4px;
        transition: color 0.15s;
      }
      .crop-modal-close:hover {
        color: #f8fafc;
      }
      .crop-modal-body {
        position: relative;
        background: #090d16;
        width: 100%;
        height: 52vh;
        min-height: 260px;
        max-height: 480px;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
      }
      .crop-modal-body img {
        display: block;
        max-width: 100%;
      }
      .crop-modal-toolbar {
        padding: 0.6rem 1rem;
        background: #131d2e;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
        justify-content: center;
      }
      .crop-tool-btn {
        background: #1e293b;
        color: #cbd5e1;
        border: 1px solid rgba(255, 255, 255, 0.12);
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 0.82rem;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        user-select: none;
        transition: all 0.15s;
      }
      .crop-tool-btn:hover {
        background: #334155;
        color: #fff;
        border-color: rgba(255, 255, 255, 0.25);
      }
      .crop-tool-btn.active {
        background: #f59e0b;
        color: #000;
        border-color: #f59e0b;
        font-weight: 700;
      }
      .crop-modal-footer {
        padding: 0.8rem 1.2rem;
        background: #0f172a;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        align-items: center;
      }
      .crop-btn-cancel {
        background: #334155;
        color: #f8fafc;
        border: 1px solid rgba(255, 255, 255, 0.12);
        padding: 0.55rem 1.1rem;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.15s;
      }
      .crop-btn-cancel:hover {
        background: #475569;
      }
      .crop-btn-done {
        background: #f59e0b;
        color: #000000;
        border: 1px solid #d97706;
        padding: 0.55rem 1.3rem;
        border-radius: 8px;
        font-size: 0.92rem;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 4px 6px -1px rgba(245, 158, 11, 0.25);
        transition: all 0.15s;
      }
      .crop-btn-done:hover {
        background: #fbbf24;
        transform: translateY(-1px);
        box-shadow: 0 6px 12px -1px rgba(245, 158, 11, 0.4);
      }
      @media (max-width: 600px) {
        .crop-modal-dialog {
          height: 100vh;
          max-height: 100vh;
          border-radius: 0;
        }
        .crop-modal-body {
          height: calc(100vh - 210px);
          max-height: none;
        }
        .crop-tool-btn span.label {
          display: none;
        }
        .crop-tool-btn {
          padding: 8px 12px;
          font-size: 0.95rem;
        }
      }
    `;
    document.head.appendChild(style);
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
            <button type="button" class="crop-modal-close" title="Close">&times;</button>
          </div>
          <div class="crop-modal-body">
            <img id="image-cropper-target" src="" alt="To Crop">
          </div>
          <div class="crop-modal-toolbar">
            <button type="button" class="crop-tool-btn" data-action="rotate-left" title="Rotate 90° Left">
              ↺ <span class="label">Rotate Left</span>
            </button>
            <button type="button" class="crop-tool-btn" data-action="rotate-right" title="Rotate 90° Right">
              ↻ <span class="label">Rotate Right</span>
            </button>
            <button type="button" class="crop-tool-btn" data-action="zoom-in" title="Zoom In">
              🔍+ <span class="label">Zoom In</span>
            </button>
            <button type="button" class="crop-tool-btn" data-action="zoom-out" title="Zoom Out">
              🔍- <span class="label">Zoom Out</span>
            </button>
            <button type="button" class="crop-tool-btn" data-action="reset" title="Reset Crop">
              🔄 <span class="label">Reset</span>
            </button>
            <button type="button" class="crop-tool-btn active" data-ratio="free" title="Free Crop">
              Freeform
            </button>
            <button type="button" class="crop-tool-btn" data-ratio="1" title="1:1 Square">
              1:1
            </button>
          </div>
          <div class="crop-modal-footer">
            <button type="button" class="crop-btn-cancel">Cancel</button>
            <button type="button" class="crop-btn-done">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
              Done
            </button>
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
            autoCropArea: 0.95,
            restore: false,
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
