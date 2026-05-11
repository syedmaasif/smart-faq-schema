/**
 * Smart FAQ Schema — Admin JavaScript
 * Wrapped in IIFE with jQuery noConflict to avoid any conflicts with Elementor or other plugins.
 */
(function($) {
    'use strict';

    // ===== META BOX =====

    var SFAQMetaBox = {

        init: function() {
            if ($('#sfaq-meta-box-wrapper').length === 0) return;

            this.bindAddFAQ();
            this.bindRemoveFAQ();
            this.bindSortable();
            this.bindCopyShortcode();
            this.updateCount();
        },

        bindAddFAQ: function() {
            var self = this;
            $(document).on('click', '#sfaq-add-faq', function(e) {
                e.preventDefault();
                self.addFAQ();
            });
        },

        addFAQ: function() {
            var maxFaqs = (typeof sfaqAdmin !== 'undefined' && sfaqAdmin.max_faqs) ? parseInt(sfaqAdmin.max_faqs) : 20;
            var currentCount = $('#sfaq-faq-list .sfaq-faq-row').length;

            if (currentCount >= maxFaqs) {
                alert('Maximum ' + maxFaqs + ' FAQs allowed.');
                return;
            }

            // Remove empty state if present
            $('#sfaq-empty-state').remove();

            var template = $('#sfaq-row-template').html();
            if (!template) return;

            var newIndex = currentCount;
            var rowHtml = template
                .replace(/\{\{INDEX\}\}/g, newIndex)
                .replace(/\{\{NUM\}\}/g, newIndex + 1);

            $('#sfaq-faq-list').append(rowHtml);
            this.renumberRows();
            this.updateCount();

            // Focus first input of new row
            var $newRow = $('#sfaq-faq-list .sfaq-faq-row').last();
            $newRow.find('.sfaq-input-question').focus();

            // Smooth scroll to new row
            $newRow[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        },

        bindRemoveFAQ: function() {
            var self = this;
            $(document).on('click', '.sfaq-btn-remove-faq', function(e) {
                e.preventDefault();
                var confirmMsg = (typeof sfaqAdmin !== 'undefined') ? sfaqAdmin.confirm_del : 'Remove this FAQ?';
                if (confirm(confirmMsg)) {
                    $(this).closest('.sfaq-faq-row').remove();
                    self.renumberRows();
                    self.updateCount();

                    // Show empty state if no FAQs left
                    if ($('#sfaq-faq-list .sfaq-faq-row').length === 0) {
                        $('#sfaq-faq-list').append(
                            '<div class="sfaq-empty-state" id="sfaq-empty-state">' +
                            '<div class="sfaq-empty-icon">💬</div>' +
                            '<p>No FAQs added yet. Click "Add FAQ" to get started.</p>' +
                            '</div>'
                        );
                    }
                }
            });
        },

        bindSortable: function() {
            var self = this;
            if (typeof $.fn.sortable === 'undefined') return;

            $('#sfaq-faq-list').sortable({
                handle: '.sfaq-drag-handle',
                axis: 'y',
                cursor: 'grabbing',
                opacity: 0.85,
                placeholder: 'sfaq-sort-placeholder',
                tolerance: 'pointer',
                stop: function() {
                    self.renumberRows();
                }
            });
        },

        renumberRows: function() {
            $('#sfaq-faq-list .sfaq-faq-row').each(function(i) {
                var $row = $(this);
                $row.attr('data-index', i);
                $row.find('.sfaq-faq-number').text(i + 1);
                // Update name attributes with new index
                $row.find('input[name^="sfaq_faqs["]').each(function() {
                    var name = $(this).attr('name');
                    $(this).attr('name', name.replace(/sfaq_faqs\[\d+\]/, 'sfaq_faqs[' + i + ']'));
                });
                $row.find('textarea[name^="sfaq_faqs["]').each(function() {
                    var name = $(this).attr('name');
                    $(this).attr('name', name.replace(/sfaq_faqs\[\d+\]/, 'sfaq_faqs[' + i + ']'));
                });
            });
        },

        updateCount: function() {
            var count = $('#sfaq-faq-list .sfaq-faq-row').length;
            $('#sfaq-count').text(count);
        },

        bindCopyShortcode: function() {
            $(document).on('click', '.sfaq-copy-shortcode', function(e) {
                e.preventDefault();
                var code = $(this).data('code');
                var $btn = $(this);
                var $feedback = $btn.siblings('.sfaq-copy-feedback');

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(code).then(function() {
                        $feedback.stop(true, true).fadeIn(200).delay(2000).fadeOut(400);
                    });
                } else {
                    // Fallback for older browsers
                    var $temp = $('<textarea>').val(code).appendTo('body');
                    $temp[0].select();
                    try { document.execCommand('copy'); } catch(err) {}
                    $temp.remove();
                    $feedback.stop(true, true).fadeIn(200).delay(2000).fadeOut(400);
                }
            });
        }
    };

    // ===== SETTINGS PAGES =====

    var SFAQSettings = {

        init: function() {
            this.initColorPickers();
            this.initStyleCards();
            this.initRadioOptions();
            this.initSchemaTypeToggle();
        },

        initColorPickers: function() {
            if (typeof $.fn.wpColorPicker === 'undefined') return;
            $('.sfaq-color-picker').wpColorPicker({
                change: function() {
                    // No live preview needed — save applies changes globally
                },
                clear: function() {}
            });
        },

        initStyleCards: function() {
            $(document).on('change', 'input[name="sfaq_ui_style"]', function() {
                $('.sfaq-style-card').removeClass('sfaq-style-active');
                $(this).closest('.sfaq-style-card').addClass('sfaq-style-active');
            });
        },

        initRadioOptions: function() {
            $(document).on('change', '.sfaq-radio-option input[type="radio"]', function() {
                $(this).closest('.sfaq-radio-group').find('.sfaq-radio-option').removeClass('sfaq-radio-active');
                $(this).closest('.sfaq-radio-option').addClass('sfaq-radio-active');
            });
        },

        initSchemaTypeToggle: function() {
            $(document).on('change', 'input[name="sfaq_schema_type"]', function() {
                var val = $(this).val();
                if (val === 'custom') {
                    $('#sfaq-custom-schema-wrap').slideDown(200);
                } else {
                    $('#sfaq-custom-schema-wrap').slideUp(200);
                }
            });
        }
    };

    // ===== BOOT =====

    $(document).ready(function() {
        SFAQMetaBox.init();
        SFAQSettings.init();
    });

})(jQuery);
