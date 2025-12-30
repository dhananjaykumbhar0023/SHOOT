/*
 * ========================================
 * PHOTOGRAPHY WEBSITE - MAIN SCRIPT FILE
 * ========================================
 * This file contains all JavaScript functionality for the photography website
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize all functionality
    initMobileMenu();
    initEnquiryPopup();
    initContactForm();
    initNewsletterForm();
    initPortfolioFilter();
    
    // ========================================
    // 1. MOBILE MENU FUNCTIONALITY
    // ========================================
    
    function initMobileMenu() {
        const hamburgerIcon = document.querySelector('.hamburger-icon');
        const mobileMenuOverlay = document.querySelector('.mobile-menu-overlay');
        const mobileNavLinks = document.querySelectorAll('.mobile-nav a');
        
        if (!hamburgerIcon || !mobileMenuOverlay) return;
        
        hamburgerIcon.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleMobileMenu();
        });
        
        mobileMenuOverlay.addEventListener('click', function(e) {
            if (e.target === mobileMenuOverlay) {
                closeMobileMenu();
            }
        });
        
        mobileNavLinks.forEach(function(link) {
            link.addEventListener('click', function(e) {
                mobileNavLinks.forEach(function(l) {
                    l.classList.remove('active');
                    l.parentElement.classList.remove('active');
                });
                this.classList.add('active');
                this.parentElement.classList.add('active');
                closeMobileMenu();
            });
        });
        
        function toggleMobileMenu() {
            const isActive = mobileMenuOverlay.classList.contains('active');
            if (isActive) {
                closeMobileMenu();
            } else {
                openMobileMenu();
            }
        }
        
        function openMobileMenu() {
            mobileMenuOverlay.classList.add('active');
            hamburgerIcon.classList.add('active');
            document.body.classList.add('menu-open');
            document.body.style.overflow = 'hidden';
        }
        
        function closeMobileMenu() {
            mobileMenuOverlay.classList.remove('active');
            hamburgerIcon.classList.remove('active');
            document.body.classList.remove('menu-open');
            document.body.style.overflow = '';
        }
        
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                closeMobileMenu();
            }
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && mobileMenuOverlay.classList.contains('active')) {
                closeMobileMenu();
            }
        });
    }
    
    // ========================================
    // 2. ENQUIRY POPUP FUNCTIONALITY
    // ========================================
    
    function initEnquiryPopup() {
        const exploreBtn = document.querySelector('.banner-btn');
        const enquiryPopup = document.getElementById('enquiryPopup');
        const popupClose = document.querySelector('.popup-close');
        const enquiryForm = document.getElementById('enquiryForm');
        
        if (!exploreBtn || !enquiryPopup) return;
        
        exploreBtn.addEventListener('click', function(e) {
            e.preventDefault();
            openEnquiryPopup();
        });
        
        if (popupClose) {
            popupClose.addEventListener('click', function() {
                closeEnquiryPopup();
            });
        }
        
        enquiryPopup.addEventListener('click', function(e) {
            if (e.target === enquiryPopup) {
                closeEnquiryPopup();
            }
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && enquiryPopup.classList.contains('active')) {
                closeEnquiryPopup();
            }
        });
        
        if (enquiryForm) {
            enquiryForm.addEventListener('submit', function(e) {
                e.preventDefault();
                submitEnquiry();
            });
        }
        
        function openEnquiryPopup() {
            enquiryPopup.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeEnquiryPopup() {
            enquiryPopup.classList.remove('active');
            document.body.style.overflow = 'auto';
            if (enquiryForm) enquiryForm.reset();
        }
        
        function submitEnquiry() {
            const formData = new FormData(enquiryForm);
            const name = formData.get('name').trim();
            const email = formData.get('email').trim();
            
            if (!name || !email) {
                showPopupMessage('Please fill in all fields.', 'error');
                return;
            }
            
            // Show loading state
            const submitBtn = enquiryForm.querySelector('.btn-submit');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Sending...';
            submitBtn.disabled = true;
            
            // Submit to PHP backend
            fetch('send-mail.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showPopupMessage(data.message, 'success');
                    enquiryForm.reset();
                    setTimeout(() => {
                        closeEnquiryPopup();
                    }, 2000);
                } else {
                    showPopupMessage(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showPopupMessage('Sorry, there was an error sending your enquiry. Please try again.', 'error');
            })
            .finally(() => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        }
        
        function showPopupMessage(message, type) {
            let messageDiv = document.querySelector('.popup-message');
            if (!messageDiv) {
                messageDiv = document.createElement('div');
                messageDiv.className = 'popup-message';
                enquiryForm.appendChild(messageDiv);
            }
            
            messageDiv.textContent = message;
            messageDiv.className = `popup-message ${type}`;
            messageDiv.style.display = 'block';
            
            if (type === 'success') {
                setTimeout(() => {
                    messageDiv.style.display = 'none';
                }, 3000);
            }
        }
    }
    
    // ========================================
    // 3. CONTACT FORM FUNCTIONALITY
    // ========================================
    
    function initContactForm() {
        const contactForm = document.getElementById('contactForm');
        const alertMessage = document.getElementById('alertMessage');
        
        if (!contactForm) return;
        
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(contactForm);
            const name = formData.get('full_name').trim();
            const email = formData.get('email').trim();
            const message = formData.get('message').trim();
            
            if (!name || !email || !message) {
                if (alertMessage) {
                    alertMessage.innerHTML = '<div class="error-message">Please fill in all required fields.</div>';
                    alertMessage.style.display = 'block';
                }
                return;
            }
            
            // Show loading state
            const submitBtn = contactForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Sending...';
            submitBtn.disabled = true;
            
            // Submit to PHP backend
            fetch('submit.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    if (alertMessage) {
                        alertMessage.innerHTML = '<div class="success-message">' + data.message + '</div>';
                        alertMessage.style.display = 'block';
                    }
                    contactForm.reset();
                } else {
                    if (alertMessage) {
                        alertMessage.innerHTML = '<div class="error-message">' + data.message + '</div>';
                        alertMessage.style.display = 'block';
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (alertMessage) {
                    alertMessage.innerHTML = '<div class="error-message">Sorry, there was an error sending your message. Please try again.</div>';
                    alertMessage.style.display = 'block';
                }
            })
            .finally(() => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        });
    }
    
    // ========================================
    // 4. NEWSLETTER FUNCTIONALITY
    // ========================================
    
    function initNewsletterForm() {
        const newsletterForm = document.getElementById('newsletterForm');
        const newsletterEmail = document.getElementById('newsletterEmail');
        
        if (!newsletterForm) return;
        
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = newsletterEmail.value.trim();
            
            if (!email) {
                alert('Please enter your email address.');
                return;
            }
            
            // Simulate subscription
            setTimeout(() => {
                alert('Thank you for subscribing to our newsletter!');
                newsletterEmail.value = '';
            }, 1000);
        });
    }
    
    // ========================================
    // 5. PORTFOLIO FILTER FUNCTIONALITY
    // ========================================
    
    function initPortfolioFilter() {
        const filterButtons = document.querySelectorAll('.filter-btn');
        const portfolioItems = document.querySelectorAll('.portfolio-item');
        
        if (filterButtons.length === 0 || portfolioItems.length === 0) return;
        
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                const filter = this.getAttribute('data-filter');
                
                // Update active button
                filterButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                // Filter portfolio items
                portfolioItems.forEach((item, index) => {
                    const category = item.getAttribute('data-category');
                    
                    if (filter === 'all' || category === filter) {
                        setTimeout(() => {
                            item.classList.remove('hidden');
                            item.style.display = 'block';
                        }, index * 100);
                    } else {
                        item.classList.add('hidden');
                        setTimeout(() => {
                            if (item.classList.contains('hidden')) {
                                item.style.display = 'none';
                            }
                        }, 300);
                    }
                });
            });
        });
    }
});

// ========================================
// GLOBAL FUNCTIONS
// ========================================

// Authentication tab switching functionality
function switchAuthTab(tab) {
    const loginForm = document.querySelector('.login-form');
    const registrationForm = document.querySelector('.registration-form');
    const tabButtons = document.querySelectorAll('.auth-tab');
    
    // Remove active class from all buttons
    tabButtons.forEach(btn => btn.classList.remove('active'));
    
    if (tab === 'login') {
        loginForm.classList.add('active');
        registrationForm.classList.remove('active');
        tabButtons[0].classList.add('active');
    } else {
        loginForm.classList.remove('active');
        registrationForm.classList.add('active');
        tabButtons[1].classList.add('active');
    }
}

// Password visibility toggle for login form
function toggleLoginPassword() {
    const passwordInput = document.getElementById('loginPassword');
    const toggleIcon = document.querySelector('.password-toggle');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.textContent = '🙈';
    } else {
        passwordInput.type = 'password';
        toggleIcon.textContent = '👁️';
    }
}

function handleLogin() {
    const loginForm = document.getElementById('loginForm');
    const messageDiv = document.getElementById('loginMessage');
    
    if (!loginForm) return;
    
    const formData = new FormData(loginForm);
    const email = formData.get('email').trim();
    const password = formData.get('password');
    
    // Client-side validation
    if (!email || !password) {
        showMessage(messageDiv, 'Please enter both email and password.', 'error');
        return;
    }
    
    if (!isValidEmail(email)) {
        showMessage(messageDiv, 'Please enter a valid email address.', 'error');
        return;
    }
    
    showMessage(messageDiv, 'Signing in...', 'loading');
    
    // Submit to PHP backend via AJAX
    fetch('../send-mail.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(messageDiv, data.message, 'success');
            // Redirect after successful login
            if (data.redirect) {
                setTimeout(() => {
                    window.location.href = '../' + data.redirect;
                }, 1500);
            }
        } else {
            // Show error message directly on the form
            showMessage(messageDiv, data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Login error:', error);
        showMessage(messageDiv, 'Login failed. Please try again.', 'error');
    });
}

function handleSignup() {
    const signupForm = document.getElementById('signupForm');
    const messageDiv = document.getElementById('signupMessage');
    
    if (!signupForm) return;
    
    // Validate form first
    if (!validateRegistrationForm()) {
        return;
    }
    
    const formData = new FormData(signupForm);
    
    // Show loading state
    const submitBtn = signupForm.querySelector('.registration-btn');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Creating Account...';
    submitBtn.disabled = true;
    
    showMessage(messageDiv, 'Creating your account and booking...', 'loading');
    
    fetch('../send-mail.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showMessage(messageDiv, '✅ ' + data.message, 'success');
            signupForm.reset();
            
            // Redirect to dashboard after success
            setTimeout(() => {
                if (data.redirect) {
                    window.location.href = '../' + data.redirect;
                } else {
                    switchAuthTab('login');
                }
            }, 2000);
        } else {
            showMessage(messageDiv, '❌ ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage(messageDiv, '❌ An error occurred. Please try again.', 'error');
    })
    .finally(() => {
        // Reset button state
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
}


// Helper functions
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function isValidPhone(phone) {
    const phoneRegex = /^[\d\s\-\+\(\)]{10,}$/;
    return phoneRegex.test(phone);
}

function isValidDate(dateString) {
    const selectedDate = new Date(dateString);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return selectedDate >= today;
}

function showMessage(element, message, type) {
    if (!element) return;
    
    element.textContent = message;
    element.className = 'form-message ' + type;
    element.style.display = 'block';
    
    if (type === 'success' || type === 'error') {
        setTimeout(() => {
            element.style.display = 'none';
        }, 5000);
    }
}

function showThankYouPage() {
    const thankYouSection = document.getElementById('thankYouPage');
    if (thankYouSection) {
        thankYouSection.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function goBackHome() {
    const thankYouSection = document.getElementById('thankYouPage');
    if (thankYouSection) {
        thankYouSection.classList.remove('active');
    }
    document.body.style.overflow = 'auto';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function goToServices() {
    const thankYouSection = document.getElementById('thankYouPage');
    if (thankYouSection) {
        thankYouSection.classList.remove('active');
    }
    document.body.style.overflow = 'auto';
    
    setTimeout(() => {
        const servicesSection = document.querySelector('.offers-section');
        if (servicesSection) {
            servicesSection.scrollIntoView({ behavior: 'smooth' });
        }
    }, 300);
}

// Cart functionality
function showCart() {
    const cartSection = document.getElementById('cart');
    const bookingSection = document.getElementById('booking');
    
    if (cartSection && bookingSection) {
        cartSection.style.display = 'block';
        bookingSection.style.display = 'none';
        
        // Smooth scroll to cart
        cartSection.scrollIntoView({ behavior: 'smooth' });
    }
}

function closeCart() {
    const cartSection = document.getElementById('cart');
    const bookingSection = document.getElementById('booking');
    
    if (cartSection && bookingSection) {
        cartSection.style.display = 'none';
        bookingSection.style.display = 'block';
        
        // Smooth scroll to booking section
        bookingSection.scrollIntoView({ behavior: 'smooth' });
    }
}

// Package cart functionality
function updateQuantity(change) {
    const qtyElement = document.getElementById('quantity');
    const subtotalElement = document.getElementById('subtotal');
    const finalTotalElement = document.getElementById('finalTotal');
    
    if (qtyElement) {
        let currentQty = parseInt(qtyElement.textContent);
        let newQty = currentQty + change;
        
        if (newQty >= 1) {
            qtyElement.textContent = newQty;
            
            const basePrice = 14999;
            const newSubtotal = basePrice * newQty;
            
            if (subtotalElement) {
                subtotalElement.textContent = `₹${newSubtotal.toLocaleString()}`;
            }
            
            // Calculate total with additional services
            calculateTotal();
        }
    }
}

function calculateTotal() {
    const quantityElement = document.getElementById('quantity');
    const finalTotalElement = document.getElementById('finalTotal');
    
    if (!quantityElement || !finalTotalElement) return;
    
    const quantity = parseInt(quantityElement.textContent);
    const basePrice = 14999;
    let total = basePrice * quantity;
    
    // Add additional services
    const checkboxes = document.querySelectorAll('.service-item input[type="checkbox"]:checked');
    checkboxes.forEach(checkbox => {
        total += parseInt(checkbox.value) * quantity;
    });
    
    finalTotalElement.textContent = `₹${total.toLocaleString()}`;
}

// Scroll to booking form function
function scrollToBookingForm() {
    const bookingSection = document.getElementById('booking');
    if (bookingSection) {
        bookingSection.scrollIntoView({ behavior: 'smooth' });
        
        // Close cart and show booking form
        closeCart();
        
        // Focus on the registration tab
        setTimeout(() => {
            showSignupTab();
        }, 500);
    }
}

function showSignupTab() {
    const loginForm = document.getElementById('loginForm');
    const signupForm = document.getElementById('signupForm');
    const tabs = document.querySelectorAll('.auth-tab');
    
    // Update tab states
    tabs.forEach(tab => tab.classList.remove('active'));
    tabs[1].classList.add('active');
    
    // Update form states
    if (loginForm && signupForm) {
        loginForm.classList.remove('active');
        signupForm.classList.add('active');
    }
}

// Image slider functionality
let currentSlideIndex = 0;
const totalSlides = 10;
const slidesPerView = 4;
const totalPages = Math.ceil(totalSlides / slidesPerView);

function moveSlider(direction) {
    const sliderTrack = document.getElementById('sliderTrack');
    
    if (!sliderTrack) return;
    
    currentSlideIndex += direction;
    
    if (currentSlideIndex >= totalPages) {
        currentSlideIndex = 0;
    } else if (currentSlideIndex < 0) {
        currentSlideIndex = totalPages - 1;
    }
    
    const translateX = -currentSlideIndex * 40;
    sliderTrack.style.transform = `translateX(${translateX}%)`;
}

// Time period handling for booking form
document.addEventListener('DOMContentLoaded', function() {
    const startTimeInput = document.getElementById('start_time');
    const endTimeInput = document.getElementById('end_time');
    
    if (startTimeInput && endTimeInput) {
        function updateTimePeriod(timeInput) {
            const timeValue = timeInput.value;
            const periodSpan = timeInput.parentElement.querySelector('.time-period');
            
            if (timeValue && periodSpan) {
                const [hours, minutes] = timeValue.split(':');
                const hour24 = parseInt(hours);
                const period = hour24 >= 12 ? 'PM' : 'AM';
                periodSpan.textContent = period;
            }
        }
        
        startTimeInput.addEventListener('change', function() {
            updateTimePeriod(this);
        });
        
        endTimeInput.addEventListener('change', function() {
            updateTimePeriod(this);
        });
        
        updateTimePeriod(startTimeInput);
        updateTimePeriod(endTimeInput);
    }
    
    // Additional services functionality
    const serviceCheckboxes = document.querySelectorAll('.service-item input[type="checkbox"]');
    serviceCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', calculateTotal);
    });
});

/*
 * ========================================
 * ENHANCED PHOTOGRAPHY BOOKING SYSTEM
 * ========================================
 * Modern, interactive booking system with portfolio gallery
 */

// Global Variables
let currentStep = 0;
let selectedPackageData = {};
let currentLightboxIndex = 0;
let portfolioImages = [];

// Initialize everything when DOM loads
document.addEventListener('DOMContentLoaded', function() {
    initializeBookingSystem();
    initializePortfolio();
    initializeHeroSlider();
    initializeSmoothScrolling();
});

// ========================================
// BOOKING SYSTEM INITIALIZATION
// ========================================

function initializeBookingSystem() {
    const form = document.getElementById('bookingForm');
    if (!form) return;
    
    // Set minimum date to today
    const dateInput = document.getElementById('event_date');
    if (dateInput) {
        const today = new Date().toISOString().split('T')[0];
        dateInput.setAttribute('min', today);
    }
    
    // Initialize first step
    showStep(0);
    
    // Add form submission handler
    form.addEventListener('submit', handleFormSubmission);
    
    // Add real-time validation
    addRealTimeValidation();
    
    console.log('Booking system initialized');
}

// ========================================
// PORTFOLIO SYSTEM
// ========================================

function initializePortfolio() {
    // Initialize portfolio filter
    const filterButtons = document.querySelectorAll('.filter-btn');
    filterButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const filter = this.getAttribute('data-filter');
            filterPortfolio(filter);
            
            // Update active button
            filterButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
        });
    });
    
    // Collect all portfolio images for lightbox navigation
    collectPortfolioImages();
    
    console.log('Portfolio system initialized');
}

function collectPortfolioImages() {
    const portfolioItems = document.querySelectorAll('.portfolio-item');
    portfolioImages = Array.from(portfolioItems).map(item => {
        const img = item.querySelector('img');
        const info = item.querySelector('.portfolio-info');
        return {
            src: img.src,
            title: info ? info.querySelector('h4').textContent : 'Portfolio Image',
            description: info ? info.querySelector('p').textContent : 'Beautiful photography'
        };
    });
}

function filterPortfolio(filter) {
    const portfolioItems = document.querySelectorAll('.portfolio-item');
    
    portfolioItems.forEach((item, index) => {
        const category = item.getAttribute('data-category');
        const shouldShow = filter === 'all' || category === filter;
        
        if (shouldShow) {
            item.style.display = 'block';
            setTimeout(() => {
                item.style.opacity = '1';
                item.style.transform = 'translateY(0)';
            }, index * 100);
        } else {
            item.style.opacity = '0';
            item.style.transform = 'translateY(20px)';
            setTimeout(() => {
                item.style.display = 'none';
            }, 300);
        }
    });
}

// ========================================
// HERO SLIDER
// ========================================

function initializeHeroSlider() {
    const slides = document.querySelectorAll('.slide');
    if (slides.length === 0) return;
    
    let currentSlide = 0;
    
    function nextSlide() {
        slides[currentSlide].classList.remove('active');
        currentSlide = (currentSlide + 1) % slides.length;
        slides[currentSlide].classList.add('active');
    }
    
    // Auto-advance slides every 5 seconds
    setInterval(nextSlide, 5000);
    
    console.log('Hero slider initialized');
}

// ========================================
// LIGHTBOX FUNCTIONALITY
// ========================================

function openLightbox(imageSrc, title = 'Portfolio Image', description = 'Beautiful photography') {
    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.getElementById('lightbox-img');
    const lightboxTitle = document.getElementById('lightbox-title');
    const lightboxDescription = document.getElementById('lightbox-description');
    
    if (!lightbox || !lightboxImg) return;
    
    // Find current image index
    currentLightboxIndex = portfolioImages.findIndex(img => img.src === imageSrc);
    if (currentLightboxIndex === -1) currentLightboxIndex = 0;
    
    // Set image and info
    lightboxImg.src = imageSrc;
    if (lightboxTitle) lightboxTitle.textContent = title;
    if (lightboxDescription) lightboxDescription.textContent = description;
    
    // Show lightbox
    lightbox.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // Add keyboard navigation
    document.addEventListener('keydown', handleLightboxKeyboard);
}

function closeLightbox() {
    const lightbox = document.getElementById('lightbox');
    if (lightbox) {
        lightbox.style.display = 'none';
        document.body.style.overflow = 'auto';
        document.removeEventListener('keydown', handleLightboxKeyboard);
    }
}

function previousImage() {
    if (portfolioImages.length === 0) return;
    
    currentLightboxIndex = (currentLightboxIndex - 1 + portfolioImages.length) % portfolioImages.length;
    updateLightboxImage();
}

function nextImage() {
    if (portfolioImages.length === 0) return;
    
    currentLightboxIndex = (currentLightboxIndex + 1) % portfolioImages.length;
    updateLightboxImage();
}

function updateLightboxImage() {
    const currentImage = portfolioImages[currentLightboxIndex];
    const lightboxImg = document.getElementById('lightbox-img');
    const lightboxTitle = document.getElementById('lightbox-title');
    const lightboxDescription = document.getElementById('lightbox-description');
    
    if (lightboxImg) lightboxImg.src = currentImage.src;
    if (lightboxTitle) lightboxTitle.textContent = currentImage.title;
    if (lightboxDescription) lightboxDescription.textContent = currentImage.description;
}

function handleLightboxKeyboard(e) {
    switch(e.key) {
        case 'Escape':
            closeLightbox();
            break;
        case 'ArrowLeft':
            previousImage();
            break;
        case 'ArrowRight':
            nextImage();
            break;
    }
}

function likeImage() {
    showNotification('❤️ Added to favorites!', 'success');
}

function shareImage() {
    if (navigator.share) {
        navigator.share({
            title: 'Beautiful Photography',
            text: 'Check out this amazing photo!',
            url: window.location.href
        });
    } else {
        // Fallback: copy to clipboard
        navigator.clipboard.writeText(window.location.href);
        showNotification('📋 Link copied to clipboard!', 'success');
    }
}

function loadMoreImages() {
    showNotification('📸 Loading more amazing work...', 'info');
    // Simulate loading more images
    setTimeout(() => {
        showNotification('✨ More photos coming soon!', 'success');
    }, 1500);
}

// ========================================
// STEP NAVIGATION
// ========================================

function showStep(stepIndex) {
    const steps = document.querySelectorAll('.form-step');
    const indicators = document.querySelectorAll('.step-indicator');
    const progressFill = document.querySelector('.progress-fill');
    
    if (steps.length === 0) return;
    
    // Hide all steps
    steps.forEach(step => step.classList.remove('active'));
    
    // Show current step
    if (steps[stepIndex]) {
        steps[stepIndex].classList.add('active');
    }
    
    // Update indicators
    indicators.forEach((indicator, index) => {
        indicator.classList.remove('active', 'completed');
        if (index < stepIndex) {
            indicator.classList.add('completed');
        } else if (index === stepIndex) {
            indicator.classList.add('active');
        }
    });
    
    // Update progress bar
    if (progressFill) {
        const progress = ((stepIndex + 1) / steps.length) * 100;
        progressFill.style.width = `${progress}%`;
    }
    
    currentStep = stepIndex;
    updateBookingSummary();
    
    // Scroll to top of form
    const bookingSection = document.getElementById('booking');
    if (bookingSection) {
        bookingSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function nextStep() {
    const steps = document.querySelectorAll('.form-step');
    
    if (currentStep < steps.length - 1) {
        if (validateCurrentStep()) {
            showStep(currentStep + 1);
        }
    }
}

function prevStep() {
    if (currentStep > 0) {
        showStep(currentStep - 1);
    }
}

// ========================================
// FORM VALIDATION
// ========================================

function validateCurrentStep() {
    const currentStepElement = document.querySelectorAll('.form-step')[currentStep];
    if (!currentStepElement) return true;
    
    const requiredFields = currentStepElement.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        const isFieldValid = validateField(field);
        if (!isFieldValid) {
            isValid = false;
        }
    });
    
    // Special validation for package selection
    if (currentStep === 2) {
        const selectedPackage = document.getElementById('selectedPackage');
        if (!selectedPackage || !selectedPackage.value) {
            showNotification('Please select a package before proceeding.', 'error');
            isValid = false;
        }
    }
    
    if (!isValid) {
        showNotification('Please fill in all required fields correctly.', 'error');
    }
    
    return isValid;
}

function validateField(field) {
    const value = field.value.trim();
    const fieldType = field.type;
    const fieldName = field.name;
    let isValid = true;
    let message = '';
    
    // Check if required field is empty
    if (field.hasAttribute('required') && !value) {
        isValid = false;
        message = 'This field is required';
    }
    
    // Specific validations
    if (value && fieldType === 'email') {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            isValid = false;
            message = 'Please enter a valid email address';
        }
    }
    
    if (value && fieldType === 'tel') {
        const phoneRegex = /^[\+]?[\d\s\-\(\)]{10,}$/;
        if (!phoneRegex.test(value)) {
            isValid = false;
            message = 'Please enter a valid phone number';
        }
    }
    
    if (value && fieldType === 'date') {
        const selectedDate = new Date(value);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        if (selectedDate < today) {
            isValid = false;
            message = 'Event date cannot be in the past';
        }
    }
    
    // Update field appearance and feedback
    updateFieldFeedback(field, isValid, message);
    
    return isValid;
}

function updateFieldFeedback(field, isValid, message) {
    const feedback = field.parentElement.querySelector('.input-feedback');
    
    // Update field styling
    if (isValid) {
        field.style.borderColor = 'var(--success-color)';
        field.style.boxShadow = '0 0 0 3px rgba(0, 210, 211, 0.1)';
        if (feedback) {
            feedback.textContent = '✓ Looks good!';
            feedback.className = 'input-feedback success';
        }
    } else {
        field.style.borderColor = 'var(--error-color)';
        field.style.boxShadow = '0 0 0 3px rgba(255, 56, 56, 0.1)';
        if (feedback) {
            feedback.textContent = message;
            feedback.className = 'input-feedback error';
        }
    }
}

function addRealTimeValidation() {
    const formInputs = document.querySelectorAll('.form-input, .form-select, .form-textarea');
    
    formInputs.forEach(input => {
        input.addEventListener('blur', () => validateField(input));
        input.addEventListener('input', () => {
            // Clear error styling on input
            input.style.borderColor = '';
            input.style.boxShadow = '';
            const feedback = input.parentElement.querySelector('.input-feedback');
            if (feedback) {
                feedback.textContent = '';
                feedback.className = 'input-feedback';
            }
            
            // Update summary in real-time
            updateBookingSummary();
        });
    });
}

// ========================================
// PACKAGE SELECTION
// ========================================

function selectPackage(packageName, price) {
    // Remove previous selection
    document.querySelectorAll('.package-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    // Add selection to clicked card
    event.currentTarget.classList.add('selected');
    
    // Store package data
    selectedPackageData = {
        name: packageName,
        price: price
    };
    
    // Update hidden fields
    document.getElementById('selectedPackage').value = packageName;
    document.getElementById('selectedPrice').value = price;
    
    // Update summary
    updateBookingSummary();
    
    showNotification(`✨ ${packageName} selected!`, 'success');
}

// ========================================
// EVENT TYPE HANDLING
// ========================================

function updateEventImages(eventType) {
    const slides = document.querySelectorAll('.slide');
    
    // Hide all slides first
    slides.forEach(slide => slide.classList.remove('active'));
    
    // Show relevant slide based on event type
    let targetCategory = 'wedding'; // default
    
    switch(eventType.toLowerCase()) {
        case 'pre-wedding':
            targetCategory = 'prewedding';
            break;
        case 'engagement':
            targetCategory = 'engagement';
            break;
        case 'wedding':
        case 'reception':
        case 'anniversary':
            targetCategory = 'wedding';
            break;
    }
    
    const targetSlide = document.querySelector(`[data-category="${targetCategory}"]`);
    if (targetSlide) {
        setTimeout(() => {
            targetSlide.classList.add('active');
        }, 300);
    }
    
    updateBookingSummary();
}

// ========================================
// BOOKING SUMMARY
// ========================================

function updateBookingSummary() {
    const form = document.getElementById('bookingForm');
    if (!form) return;
    
    const formData = new FormData(form);
    
    // Update personal info
    updateSummaryField('summary-name', formData.get('name'));
    updateSummaryField('summary-email', formData.get('email'));
    updateSummaryField('summary-phone', formData.get('phone'));
    
    // Update event details
    updateSummaryField('summary-event', formData.get('event_type'));
    updateSummaryField('summary-location', formData.get('location'));
    
    // Update date and time
    const eventDate = formData.get('event_date');
    const eventTime = formData.get('event_time');
    let dateTimeText = '-';
    
    if (eventDate) {
        const date = new Date(eventDate);
        dateTimeText = date.toLocaleDateString('en-IN', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        
        if (eventTime) {
            dateTimeText += ` at ${eventTime}`;
        }
    }
    
    updateSummaryField('summary-datetime', dateTimeText);
    
    // Update package and price
    updateSummaryField('summary-package', selectedPackageData.name || '-');
    
    const price = selectedPackageData.price || 0;
    const priceText = price > 0 ? `₹${price.toLocaleString()}` : '₹0';
    updateSummaryField('summary-price', priceText);
}

function updateSummaryField(elementId, value) {
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = value || '-';
    }
}

// ========================================
// FORM SUBMISSION
// ========================================

function handleFormSubmission(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    
    // Show loading overlay
    showLoadingOverlay(true);
    
    // Try PHPMailer first, then fallback to simple mail
    submitBookingWithFallback(formData);
}

async function submitBookingWithFallback(formData) {
    try {
        // First attempt: Try send-mail.php
        console.log('Attempting booking submission...');
        const response = await fetch('../send-mail.php', {
            method: 'POST',
            body: formData
        });
        
        const responseText = await response.text();
        console.log('Response:', responseText);
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.log('Response is not JSON, checking for success indicators...');
            // If response is not JSON, check for success indicators
            if (responseText.includes('success') || responseText.includes('confirmed')) {
                data = { status: 'success', message: 'Booking confirmed successfully!' };
            } else {
                throw new Error('Invalid response format');
            }
        }
        
        if (data.status === 'success') {
            console.log('Booking successful');
            showLoadingOverlay(false);
            showSuccessModal();
            resetForm();
            showNotification('✅ Booking confirmed! Check your email for details.', 'success');
            return;
        } else {
            throw new Error(data.message || 'Booking failed');
        }
        
    } catch (error) {
        console.log('Booking failed:', error);
        showLoadingOverlay(false);
        
        // Show error message
        showNotification('❌ Booking failed: ' + error.message + '. Please try again.', 'error');
    }
}

function saveBookingLocally(formData) {
    try {
        const bookingData = {
            timestamp: new Date().toISOString(),
            booking_id: 'LOCAL_' + Date.now(),
            name: formData.get('name'),
            email: formData.get('email'),
            phone: formData.get('phone'),
            event_type: formData.get('event_type'),
            event_date: formData.get('event_date'),
            event_time: formData.get('event_time'),
            location: formData.get('location'),
            package: formData.get('package'),
            package_price: formData.get('package_price'),
            message: formData.get('message'),
            status: 'local_backup'
        };
        
        // Save to localStorage as backup
        const existingBookings = JSON.parse(localStorage.getItem('photography_bookings') || '[]');
        existingBookings.push(bookingData);
        localStorage.setItem('photography_bookings', JSON.stringify(existingBookings));
        
        console.log('Booking saved locally:', bookingData);
        
        // Also try to send a simple notification email
        sendSimpleNotification(bookingData);
        
    } catch (error) {
        console.error('Failed to save booking locally:', error);
    }
}

function sendSimpleNotification(bookingData) {
    // Create a simple form to send basic notification
    const notificationForm = new FormData();
    notificationForm.append('action', 'simple_notification');
    notificationForm.append('booking_data', JSON.stringify(bookingData));
    
    fetch('../booking-simple.php', {
        method: 'POST',
        body: notificationForm
    }).catch(error => {
        console.log('Simple notification also failed:', error);
    });
}

function resetForm() {
    const form = document.getElementById('bookingForm');
    if (form) {
        form.reset();
        selectedPackageData = {};
        showStep(0);
        
        // Clear package selections
        document.querySelectorAll('.package-card').forEach(card => {
            card.classList.remove('selected');
        });
        
        // Clear field styling
        document.querySelectorAll('.form-input, .form-select, .form-textarea').forEach(field => {
            field.style.borderColor = '';
            field.style.boxShadow = '';
        });
        
        // Clear feedback messages
        document.querySelectorAll('.input-feedback').forEach(feedback => {
            feedback.textContent = '';
            feedback.className = 'input-feedback';
        });
    }
}

// ========================================
// MODAL FUNCTIONS
// ========================================

function showSuccessModal() {
    const modal = document.getElementById('successModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Auto-close after 10 seconds
        setTimeout(() => {
            closeSuccessModal();
        }, 10000);
    }
}

function closeSuccessModal() {
    const modal = document.getElementById('successModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

function showLoadingOverlay(show) {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = show ? 'flex' : 'none';
    }
}

// ========================================
// NOTIFICATION SYSTEM
// ========================================

function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    // Style the notification
    Object.assign(notification.style, {
        position: 'fixed',
        top: '100px',
        right: '20px',
        padding: '16px 24px',
        borderRadius: '12px',
        color: 'white',
        fontWeight: '600',
        fontSize: '0.95rem',
        zIndex: '10000',
        transform: 'translateX(100%)',
        transition: 'transform 0.3s ease',
        maxWidth: '350px',
        wordWrap: 'break-word',
        boxShadow: '0 8px 32px rgba(0, 0, 0, 0.3)',
        backdropFilter: 'blur(10px)'
    });
    
    // Set background color based on type
    switch(type) {
        case 'success':
            notification.style.background = 'linear-gradient(135deg, #00d2d3, #00a8a9)';
            break;
        case 'error':
            notification.style.background = 'linear-gradient(135deg, #ff3838, #cc2d2d)';
            break;
        case 'info':
            notification.style.background = 'linear-gradient(135deg, #4834d4, #3742c7)';
            break;
        default:
            notification.style.background = 'linear-gradient(135deg, #6c757d, #5a6268)';
    }
    
    // Add to page
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 5000);
}

// ========================================
// SMOOTH SCROLLING
// ========================================

function initializeSmoothScrolling() {
    // Smooth scroll for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

function scrollToBooking() {
    const bookingSection = document.getElementById('booking');
    if (bookingSection) {
        bookingSection.scrollIntoView({ behavior: 'smooth' });
    }
}

function scrollToPortfolio() {
    const portfolioSection = document.getElementById('portfolio');
    if (portfolioSection) {
        portfolioSection.scrollIntoView({ behavior: 'smooth' });
    }
}

// ========================================
// UTILITY FUNCTIONS
// ========================================

// Debounce function for performance
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        minimumFractionDigits: 0
    }).format(amount);
}

// Format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-IN', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

// ========================================
// LEGACY SUPPORT (for existing code)
// ========================================

// Keep these functions for backward compatibility
window.nextStep = nextStep;
window.prevStep = prevStep;
window.selectPackage = selectPackage;
window.updateEventImages = updateEventImages;
window.openLightbox = openLightbox;
window.closeLightbox = closeLightbox;
window.previousImage = previousImage;
window.nextImage = nextImage;
window.likeImage = likeImage;
window.shareImage = shareImage;
window.loadMoreImages = loadMoreImages;
window.showSuccessModal = showSuccessModal;
window.closeSuccessModal = closeSuccessModal;
window.scrollToBooking = scrollToBooking;
window.scrollToPortfolio = scrollToPortfolio;

console.log('Enhanced Photography Booking System loaded successfully! 📸✨');

// ========================================
// QR CODE CAROUSEL FUNCTIONALITY
// ========================================

let currentQRIndex = 1;
const totalQRCodes = 4;

function nextQR() {
    currentQRIndex = currentQRIndex >= totalQRCodes ? 1 : currentQRIndex + 1;
    showQRSlide(currentQRIndex);
}

function previousQR() {
    currentQRIndex = currentQRIndex <= 1 ? totalQRCodes : currentQRIndex - 1;
    showQRSlide(currentQRIndex);
}

function currentQR(index) {
    currentQRIndex = index;
    showQRSlide(currentQRIndex);
}

function showQRSlide(index) {
    // Hide all slides
    const slides = document.querySelectorAll('.qr-slide');
    const dots = document.querySelectorAll('.qr-dot');
    
    slides.forEach((slide, i) => {
        slide.classList.remove('active', 'prev');
        if (i + 1 === index) {
            slide.classList.add('active');
        } else if (i + 1 < index) {
            slide.classList.add('prev');
        }
    });
    
    // Update dots
    dots.forEach((dot, i) => {
        dot.classList.remove('active');
        if (i + 1 === index) {
            dot.classList.add('active');
        }
    });
    
    // Update current index
    currentQRIndex = index;
}

// Auto-advance QR codes every 8 seconds
setInterval(() => {
    nextQR();
}, 8000);

// Add swipe support for mobile
let startX = 0;
let endX = 0;

document.addEventListener('DOMContentLoaded', function() {
    const qrContainer = document.querySelector('.qr-code-container');
    
    if (qrContainer) {
        qrContainer.addEventListener('touchstart', function(e) {
            startX = e.touches[0].clientX;
        });
        
        qrContainer.addEventListener('touchend', function(e) {
            endX = e.changedTouches[0].clientX;
            handleSwipe();
        });
    }
});

function handleSwipe() {
    const swipeThreshold = 50;
    const diff = startX - endX;
    
    if (Math.abs(diff) > swipeThreshold) {
        if (diff > 0) {
            // Swipe left - next QR
            nextQR();
        } else {
            // Swipe right - previous QR
            previousQR();
        }
    }
}

// Make functions globally available
window.nextQR = nextQR;
window.previousQR = previousQR;
window.currentQR = currentQR;

console.log('QR Carousel functionality loaded! 📱✨');