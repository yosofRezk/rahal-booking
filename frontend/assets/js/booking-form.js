/**
 * Rahal Booking Plugin — Frontend Form Script
 *
 * Handles live price calculation preview, AJAX submission, loading state,
 * error presentation, and confirmation view replacement.
 */

(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var forms = document.querySelectorAll('.rahl-booking-form');
		if (forms.length) {
			forms.forEach(function (form) {
				initBookingForm(form);
			});
		}

		initBookingModals();
	});

	// ── إدارة نوافذ الـ Popup Modal ──
	function initBookingModals() {
		// أزرار فتح الـ Modal
		var openButtons = document.querySelectorAll('[data-modal-target]');
		openButtons.forEach(function (btn) {
			btn.addEventListener('click', function (e) {
				e.preventDefault();
				var targetId = btn.getAttribute('data-modal-target');
				var modal = document.getElementById(targetId);
				if (modal) {
					openModal(modal);
				}
			});
		});

		// أزرار وخلفيات إغلاق الـ Modal
		var closeElements = document.querySelectorAll('[data-modal-close]');
		closeElements.forEach(function (el) {
			el.addEventListener('click', function (e) {
				e.preventDefault();
				var modal = el.closest('.rahl-modal');
				if (modal) {
					closeModal(modal);
				}
			});
		});

		// الإغلاق عند الضغط على زر Esc من لوحة المفاتيح
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' || e.keyCode === 27) {
				var activeModal = document.querySelector('.rahl-modal.rahl-modal-active');
				if (activeModal) {
					closeModal(activeModal);
				}
			}
		});

		function openModal(modal) {
			modal.classList.add('rahl-modal-active');
			modal.setAttribute('aria-hidden', 'false');
			document.body.classList.add('rahl-modal-open');

			// التركيز على أول حقل في الفورم داخل الـ Modal
			var firstInput = modal.querySelector('input:not([type="hidden"]):not([tabindex="-1"])');
			if (firstInput) {
				setTimeout(function () {
					firstInput.focus();
				}, 100);
			}
		}

		function closeModal(modal) {
			modal.classList.remove('rahl-modal-active');
			modal.setAttribute('aria-hidden', 'true');

			// التحقق إذا كانت هناك نوافذ أخرى مفتوحة قبل إزالة كلاس الـ body
			if (!document.querySelector('.rahl-modal.rahl-modal-active')) {
				document.body.classList.remove('rahl-modal-open');
			}
		}
	}

	function initBookingForm(form) {
		var guestsInput = form.querySelector('.rahl-input-guests');
		var pricePreview = form.querySelector('.rahl-price-preview-amount');
		var feedback = form.querySelector('.rahl-form-feedback');
		var submitBtn = form.querySelector('.rahl-submit-btn');
		var btnText = form.querySelector('.rahl-btn-text');
		var spinner = form.querySelector('.rahl-spinner');

		var priceMinor = parseInt(form.getAttribute('data-price-minor'), 10) || 0;
		var currency = form.getAttribute('data-currency') || 'USD';

		// ── تحديث المعاينة الحية للسعر عند تغيير عدد الأفراد ──
		if (guestsInput && pricePreview) {
			guestsInput.addEventListener('input', function () {
				var count = parseInt(guestsInput.value, 10);
				if (isNaN(count) || count < 1) {
					count = 1;
				}
				var totalMinor = count * priceMinor;
				var major = (totalMinor / 100).toFixed(2);
				pricePreview.textContent = major + ' ' + currency;
			});
		}

		// ── إرسال الفورم عبر AJAX ──
		form.addEventListener('submit', function (e) {
			e.preventDefault();

			// تفريغ رسائل الخطأ السابقة
			feedback.style.display = 'none';
			feedback.className = 'rahl-form-feedback';
			feedback.innerHTML = '';

			// تفعيل حالة التحميل لمنع النقرات المتعددة
			setLoading(true);

			var formData = new FormData(form);

			var ajaxUrl = (window.rahlBookingData && window.rahlBookingData.ajaxUrl) ? window.rahlBookingData.ajaxUrl : '/wp-admin/admin-ajax.php';

			fetch(ajaxUrl, {
				method: 'POST',
				body: formData,
				credentials: 'same-origin',
			})
				.then(function (response) {
					return response.json();
				})
				.then(function (data) {
					if (data.success && data.data) {
						renderConfirmation(form, data.data);
					} else {
						var errorMsg = (data.data && data.data.message) ? data.data.message : 'An unexpected error occurred.';
						showError(feedback, errorMsg);
						setLoading(false);
					}
				})
				.catch(function () {
					var netError = (window.rahlBookingData && window.rahlBookingData.i18n && window.rahlBookingData.i18n.networkError)
						? window.rahlBookingData.i18n.networkError
						: 'A connection error occurred. Please try again.';
					showError(feedback, netError);
					setLoading(false);
				});
		});

		function setLoading(isLoading) {
			if (isLoading) {
				submitBtn.disabled = true;
				if (spinner) spinner.style.display = 'inline-block';
				if (btnText && window.rahlBookingData && window.rahlBookingData.i18n) {
					btnText.textContent = window.rahlBookingData.i18n.submitting;
				}
			} else {
				submitBtn.disabled = false;
				if (spinner) spinner.style.display = 'none';
				if (btnText && window.rahlBookingData && window.rahlBookingData.i18n) {
					btnText.textContent = window.rahlBookingData.i18n.submit;
				}
			}
		}

		function showError(el, message) {
			el.className = 'rahl-form-feedback rahl-feedback-error';
			el.textContent = message;
			el.style.display = 'block';
		}

		function renderConfirmation(formEl, details) {
			var wrapper = formEl.closest('.rahl-booking-wrapper');
			if (!wrapper) {
				return;
			}

			var instructionsHtml = '';
			if (details.instructions && details.instructions.trim() !== '') {
				instructionsHtml = '<div class="rahl-confirmation-instructions">' +
					'<strong>Instructions:</strong> ' + details.instructions +
				'</div>';
			}

			var confirmationHtml = '' +
				'<div class="rahl-booking-confirmation" role="status" aria-live="polite">' +
					'<div class="rahl-confirmation-icon">&#10003;</div>' +
					'<h3 class="rahl-confirmation-title">' + (details.message || 'Booking Confirmed!') + '</h3>' +
					'<div class="rahl-confirmation-details">' +
						'<div class="rahl-conf-row"><span>Booking Reference:</span> <strong>' + escapeHtml(details.reference) + '</strong></div>' +
						'<div class="rahl-conf-row"><span>Guests:</span> <strong>' + escapeHtml(String(details.guests)) + '</strong></div>' +
						'<div class="rahl-conf-row"><span>Total Amount:</span> <strong>' + escapeHtml(details.total) + '</strong></div>' +
					'</div>' +
					instructionsHtml +
				'</div>';

			wrapper.innerHTML = confirmationHtml;
		}

		function escapeHtml(string) {
			var div = document.createElement('div');
			div.appendChild(document.createTextNode(string));
			return div.innerHTML;
		}
	}
})();
