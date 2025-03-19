/**
 * EVolve QR Code Generator
 * A standalone utility for generating QR codes with JavaScript
 */
const EVolveQR = {
    /**
     * Generate a QR code and place it in the specified container
     * @param {string} data - The data to encode in the QR code
     * @param {string|HTMLElement} container - The container element or its ID
     * @param {Object} options - Configuration options
     */
    generate: function(data, container, options = {}) {
        // Default options
        const defaults = {
            size: 200,           // QR code size in pixels
            margin: 0,           // Margin around QR code
            colorDark: '#000',   // Dark color
            colorLight: '#FFF',  // Light color
            logo: null,          // Optional logo to place in center
            logoWidth: 60,       // Logo width
            logoHeight: 60,      // Logo height
            onSuccess: null      // Callback when QR is generated
        };
        
        // Merge defaults with provided options
        const settings = {...defaults, ...options};
        
        // Get container element
        const containerEl = typeof container === 'string' 
            ? document.getElementById(container) 
            : container;
            
        if (!containerEl) {
            console.error('QR Code container not found');
            return;
        }
        
        // Load QR code library if not already loaded
        if (typeof qrcode === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js';
            script.onload = () => this._createQR(data, containerEl, settings);
            document.head.appendChild(script);
        } else {
            this._createQR(data, containerEl, settings);
        }
    },
    
    /**
     * Internal method to create QR code
     * @private
     */
    _createQR: function(data, container, settings) {
        // Generate QR code
        const qr = qrcode(0, 'L'); // Type 0 = auto, L = Low error correction
        qr.addData(data);
        qr.make();
        
        // Create the QR code image
        const qrImage = qr.createImgTag(settings.size / 25, settings.margin);
        
        // If logo is specified, we'll need to overlay it
        if (settings.logo) {
            // First insert the QR code
            container.innerHTML = qrImage;
            
            // Then add a logo overlay
            const img = container.querySelector('img');
            
            img.onload = () => {
                // Create wrapper for positioning
                const wrapper = document.createElement('div');
                wrapper.style.position = 'relative';
                wrapper.style.display = 'inline-block';
                
                // Move the QR code inside the wrapper
                img.parentNode.insertBefore(wrapper, img);
                wrapper.appendChild(img);
                
                // Create and add the logo
                const logo = document.createElement('img');
                logo.src = settings.logo;
                logo.width = settings.logoWidth;
                logo.height = settings.logoHeight;
                logo.style.position = 'absolute';
                logo.style.top = '50%';
                logo.style.left = '50%';
                logo.style.transform = 'translate(-50%, -50%)';
                logo.style.borderRadius = '50%';
                logo.style.backgroundColor = 'white';
                logo.style.padding = '5px';
                
                wrapper.appendChild(logo);
                
                if (typeof settings.onSuccess === 'function') {
                    settings.onSuccess(wrapper);
                }
            };
        } else {
            // Just insert the QR code without a logo
            container.innerHTML = qrImage;
            
            if (typeof settings.onSuccess === 'function') {
                settings.onSuccess(container.querySelector('img'));
            }
        }
    },
    
    /**
     * Download the QR code as a PNG image
     * @param {string|HTMLElement} container - The QR code container or its ID
     * @param {string} filename - Name for the downloaded file
     */
    download: function(container, filename = 'qrcode.png') {
        const containerEl = typeof container === 'string' 
            ? document.getElementById(container) 
            : container;
            
        if (!containerEl) {
            console.error('QR Code container not found');
            return;
        }
        
        const img = containerEl.querySelector('img');
        if (!img) {
            console.error('No QR code image found in container');
            return;
        }
        
        // Create a canvas to draw the QR code
        const canvas = document.createElement('canvas');
        canvas.width = img.width;
        canvas.height = img.height;
        
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = 'white';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.drawImage(img, 0, 0);
        
        // For containers with a logo overlay
        const logo = containerEl.querySelector('img:nth-child(2)');
        if (logo) {
            // Calculate logo position (center)
            const x = (canvas.width - logo.width) / 2;
            const y = (canvas.height - logo.height) / 2;
            ctx.drawImage(logo, x, y, logo.width, logo.height);
        }
        
        // Convert canvas to data URL and download
        const dataUrl = canvas.toDataURL('image/png');
        
        const a = document.createElement('a');
        a.href = dataUrl;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }
}; 