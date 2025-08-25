/**
 * Universal Export Utility for Budget App
 * Supports PDF, Excel (XLSX), and CSV exports across all pages
 */

class ExportUtility {
    constructor() {
        this.loadedLibraries = {
            jsPDF: false,
            html2canvas: false,
            xlsx: false
        };
    }

    /**
     * Load required libraries dynamically
     */
    async loadLibraries(formats = ['pdf', 'excel', 'csv']) {
        const promises = [];

        if (formats.includes('pdf') && !this.loadedLibraries.jsPDF) {
            promises.push(this.loadScript('https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js'));
            promises.push(this.loadScript('https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js'));
        }

        if (formats.includes('excel') && !this.loadedLibraries.xlsx) {
            promises.push(this.loadScript('https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js'));
        }

        await Promise.all(promises);
        
        this.loadedLibraries.jsPDF = window.jsPDF ? true : false;
        this.loadedLibraries.html2canvas = window.html2canvas ? true : false;
        this.loadedLibraries.xlsx = window.XLSX ? true : false;
    }

    /**
     * Load external script dynamically
     */
    loadScript(src) {
        return new Promise((resolve, reject) => {
            if (document.querySelector(`script[src="${src}"]`)) {
                resolve();
                return;
            }

            const script = document.createElement('script');
            script.src = src;
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    /**
     * Export to PDF
     */
    async exportToPDF(options = {}) {
        try {
            await this.loadLibraries(['pdf']);
            
            const {
                element = document.body,
                filename = 'export.pdf',
                title = 'Export',
                orientation = 'portrait',
                format = 'a4',
                margin = 10,
                quality = 2
            } = options;


            // Show loading indicator
            this.showExportLoading('Generating PDF...');

            const canvas = await html2canvas(element, {
                scale: quality,
                useCORS: true,
                allowTaint: true,
                backgroundColor: '#ffffff',
                logging: false,
                onclone: (clonedDoc) => {
                    // Hide any elements that shouldn't be exported
                    const hideElements = clonedDoc.querySelectorAll('.no-export, .export-btn, .loading-overlay');
                    hideElements.forEach(el => el.style.display = 'none');
                }
            });

            const imgData = canvas.toDataURL('image/png');
            const pdf = new window.jsPDF({
                orientation: orientation,
                unit: 'mm',
                format: format
            });

            const pdfWidth = pdf.internal.pageSize.getWidth() - (margin * 2);
            const pdfHeight = pdf.internal.pageSize.getHeight() - (margin * 2);
            const imgWidth = canvas.width;
            const imgHeight = canvas.height;
            const ratio = Math.min(pdfWidth / imgWidth, pdfHeight / imgHeight);
            
            const scaledWidth = imgWidth * ratio;
            const scaledHeight = imgHeight * ratio;

            // Add title
            pdf.setFontSize(16);
            pdf.text(title, margin, margin + 10);

            // Add timestamp
            pdf.setFontSize(10);
            pdf.text(`Generated on: ${new Date().toLocaleString()}`, margin, margin + 20);

            // Add image
            let yPosition = margin + 30;
            if (scaledHeight > pdfHeight - 30) {
                // Multiple pages needed
                let remainingHeight = scaledHeight;
                let sourceY = 0;
                
                while (remainingHeight > 0) {
                    const pageHeight = Math.min(remainingHeight, pdfHeight - 30);
                    const sourceHeight = (pageHeight / ratio);
                    
                    const pageCanvas = document.createElement('canvas');
                    pageCanvas.width = imgWidth;
                    pageCanvas.height = sourceHeight;
                    const pageCtx = pageCanvas.getContext('2d');
                    
                    pageCtx.drawImage(canvas, 0, sourceY, imgWidth, sourceHeight, 0, 0, imgWidth, sourceHeight);
                    const pageImgData = pageCanvas.toDataURL('image/png');
                    
                    pdf.addImage(pageImgData, 'PNG', margin, yPosition, scaledWidth, pageHeight);
                    
                    remainingHeight -= pageHeight;
                    sourceY += sourceHeight;
                    
                    if (remainingHeight > 0) {
                        pdf.addPage();
                        yPosition = margin;
                    }
                }
            } else {
                pdf.addImage(imgData, 'PNG', margin, yPosition, scaledWidth, scaledHeight);
            }

            pdf.save(filename);
            this.hideExportLoading();
            this.showExportSuccess('PDF exported successfully!');
            
        } catch (error) {
            this.hideExportLoading();
            this.showExportError('Failed to export PDF: ' + error.message);
            console.error('PDF Export Error:', error);
        }
    }

    /**
     * Export to Excel
     */
    async exportToExcel(data, options = {}) {
        try {
            await this.loadLibraries(['excel']);
            
            const {
                filename = 'export.xlsx',
                sheetName = 'Sheet1',
                title = 'Export Data'
            } = options;

            this.showExportLoading('Generating Excel file...');

            const wb = window.XLSX.utils.book_new();
            let ws;

            if (Array.isArray(data)) {
                // Data is array of objects
                ws = window.XLSX.utils.json_to_sheet(data);
            } else if (typeof data === 'object' && data.headers && data.rows) {
                // Data has headers and rows structure
                ws = window.XLSX.utils.aoa_to_sheet([data.headers, ...data.rows]);
            } else {
                throw new Error('Invalid data format for Excel export');
            }

            // Auto-size columns
            const range = window.XLSX.utils.decode_range(ws['!ref']);
            const colWidths = [];
            for (let C = range.s.c; C <= range.e.c; ++C) {
                let maxWidth = 10;
                for (let R = range.s.r; R <= range.e.r; ++R) {
                    const cellAddress = window.XLSX.utils.encode_cell({ r: R, c: C });
                    const cell = ws[cellAddress];
                    if (cell && cell.v) {
                        maxWidth = Math.max(maxWidth, cell.v.toString().length);
                    }
                }
                colWidths.push({ wch: Math.min(maxWidth + 2, 50) });
            }
            ws['!cols'] = colWidths;

            window.XLSX.utils.book_append_sheet(wb, ws, sheetName);
            window.XLSX.writeFile(wb, filename);

            this.hideExportLoading();
            this.showExportSuccess('Excel file exported successfully!');
            
        } catch (error) {
            this.hideExportLoading();
            this.showExportError('Failed to export Excel: ' + error.message);
            console.error('Excel Export Error:', error);
        }
    }

    /**
     * Export to CSV
     */
    exportToCSV(data, options = {}) {
        try {
            const {
                filename = 'export.csv',
                delimiter = ',',
                title = 'Export Data'
            } = options;

            this.showExportLoading('Generating CSV file...');

            let csvContent = '';

            if (Array.isArray(data) && data.length > 0) {
                // Data is array of objects
                const headers = Object.keys(data[0]);
                csvContent += headers.join(delimiter) + '\n';
                
                data.forEach(row => {
                    const values = headers.map(header => {
                        let value = row[header] || '';
                        // Escape quotes and wrap in quotes if necessary
                        if (typeof value === 'string' && (value.includes(delimiter) || value.includes('"') || value.includes('\n'))) {
                            value = '"' + value.replace(/"/g, '""') + '"';
                        }
                        return value;
                    });
                    csvContent += values.join(delimiter) + '\n';
                });
            } else if (typeof data === 'object' && data.headers && data.rows) {
                // Data has headers and rows structure
                csvContent += data.headers.join(delimiter) + '\n';
                data.rows.forEach(row => {
                    const values = row.map(value => {
                        if (typeof value === 'string' && (value.includes(delimiter) || value.includes('"') || value.includes('\n'))) {
                            return '"' + value.replace(/"/g, '""') + '"';
                        }
                        return value;
                    });
                    csvContent += values.join(delimiter) + '\n';
                });
            } else {
                throw new Error('Invalid data format for CSV export');
            }

            // Create and download file
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = filename;
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            this.hideExportLoading();
            this.showExportSuccess('CSV file exported successfully!');
            
        } catch (error) {
            this.hideExportLoading();
            this.showExportError('Failed to export CSV: ' + error.message);
            console.error('CSV Export Error:', error);
        }
    }

    /**
     * Show export modal with format options
     */
    showExportModal(data, options = {}) {
        const {
            title = 'Export Data',
            filename = 'export',
            formats = ['pdf', 'excel', 'csv'],
            element = null
        } = options;

        // Create modal HTML
        const modalHTML = `
            <div id="exportModal" class="export-modal-overlay">
                <div class="export-modal">
                    <div class="export-modal-header">
                        <h3>📤 Export ${title}</h3>
                        <button type="button" class="export-modal-close">&times;</button>
                    </div>
                    <div class="export-modal-body">
                        <p>Choose export format:</p>
                        <div class="export-format-buttons">
                            ${formats.includes('pdf') ? `
                                <button type="button" class="export-btn export-pdf-btn">
                                    <span class="export-icon">📄</span>
                                    <span class="export-label">PDF</span>
                                    <span class="export-desc">Visual report</span>
                                </button>
                            ` : ''}
                            ${formats.includes('excel') ? `
                                <button type="button" class="export-btn export-excel-btn">
                                    <span class="export-icon">📊</span>
                                    <span class="export-label">Excel</span>
                                    <span class="export-desc">Spreadsheet</span>
                                </button>
                            ` : ''}
                            ${formats.includes('csv') ? `
                                <button type="button" class="export-btn export-csv-btn">
                                    <span class="export-icon">📋</span>
                                    <span class="export-label">CSV</span>
                                    <span class="export-desc">Data file</span>
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Add modal to page
        document.body.insertAdjacentHTML('beforeend', modalHTML);

        // Add event listeners
        const modal = document.getElementById('exportModal');
        const closeBtn = modal.querySelector('.export-modal-close');
        
        closeBtn.addEventListener('click', () => this.hideExportModal());
        modal.addEventListener('click', (e) => {
            if (e.target === modal) this.hideExportModal();
        });

        // Format button listeners
        if (formats.includes('pdf')) {
            modal.querySelector('.export-pdf-btn').addEventListener('click', () => {
                this.hideExportModal();
                this.exportToPDF({
                    element: element || document.querySelector('.main-content') || document.body,
                    filename: `${filename}.pdf`,
                    title: title
                });
            });
        }

        if (formats.includes('excel')) {
            modal.querySelector('.export-excel-btn').addEventListener('click', () => {
                this.hideExportModal();
                this.exportToExcel(data, {
                    filename: `${filename}.xlsx`,
                    title: title
                });
            });
        }

        if (formats.includes('csv')) {
            modal.querySelector('.export-csv-btn').addEventListener('click', () => {
                this.hideExportModal();
                this.exportToCSV(data, {
                    filename: `${filename}.csv`,
                    title: title
                });
            });
        }
    }

    hideExportModal() {
        const modal = document.getElementById('exportModal');
        if (modal) {
            modal.remove();
        }
    }

    /**
     * Show loading indicator
     */
    showExportLoading(message = 'Exporting...') {
        const loadingHTML = `
            <div id="exportLoading" class="export-loading-overlay">
                <div class="export-loading">
                    <div class="export-spinner"></div>
                    <p>${message}</p>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', loadingHTML);
    }

    hideExportLoading() {
        const loading = document.getElementById('exportLoading');
        if (loading) {
            loading.remove();
        }
    }

    /**
     * Show success message
     */
    showExportSuccess(message) {
        this.showExportNotification(message, 'success');
    }

    /**
     * Show error message
     */
    showExportError(message) {
        this.showExportNotification(message, 'error');
    }

    showExportNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `export-notification export-notification-${type}`;
        notification.innerHTML = `
            <span class="export-notification-icon">${type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️'}</span>
            <span class="export-notification-message">${message}</span>
        `;
        
        document.body.appendChild(notification);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 3000);
    }

    /**
     * Extract table data from DOM element
     */
    extractTableData(tableElement) {
        const headers = [];
        const rows = [];
        
        // Get headers
        const headerRow = tableElement.querySelector('thead tr, tr:first-child');
        if (headerRow) {
            headerRow.querySelectorAll('th, td').forEach(cell => {
                headers.push(cell.textContent.trim());
            });
        }
        
        // Get data rows
        const dataRows = tableElement.querySelectorAll('tbody tr, tr:not(:first-child)');
        dataRows.forEach(row => {
            const rowData = [];
            row.querySelectorAll('td, th').forEach(cell => {
                rowData.push(cell.textContent.trim());
            });
            if (rowData.length > 0) {
                rows.push(rowData);
            }
        });
        
        return { headers, rows };
    }

    /**
     * Convert chart data to exportable format
     */
    extractChartData(chart) {
        if (!chart || !chart.data) return null;
        
        const data = [];
        const labels = chart.data.labels || [];
        const datasets = chart.data.datasets || [];
        
        if (datasets.length === 1) {
            // Single dataset - simple format
            labels.forEach((label, index) => {
                data.push({
                    Label: label,
                    Value: datasets[0].data[index] || 0
                });
            });
        } else {
            // Multiple datasets - complex format
            labels.forEach((label, index) => {
                const row = { Label: label };
                datasets.forEach(dataset => {
                    row[dataset.label || 'Data'] = dataset.data[index] || 0;
                });
                data.push(row);
            });
        }
        
        return data;
    }
}

// Global instance
window.exportUtility = new ExportUtility();
