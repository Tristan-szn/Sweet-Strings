/**
 * Sweets & Strings - Interactive Script
 * Smooth scrolling, scroll reveal animations, and UI interactions
 */

(function() {
    'use strict';

    // ========================================
    // DOM Elements
    // ========================================
    const nav = document.getElementById('nav');
    const menuToggle = document.getElementById('menuToggle');
    const mobileMenu = document.getElementById('mobileMenu');
    const toast = document.getElementById('toast');
    const newsletterForm = document.getElementById('newsletterForm');

    // ========================================
    // Navigation Scroll Effect
    // ========================================
    function handleNavScroll() {
        if (window.scrollY > 50) {
            nav.classList.add('scrolled');
        } else {
            nav.classList.remove('scrolled');
        }
    }

    window.addEventListener('scroll', handleNavScroll, { passive: true });

    // ========================================
    // Mobile Menu Toggle
    // ========================================
    if (menuToggle && mobileMenu) {
        menuToggle.addEventListener('click', function() {
            this.classList.toggle('active');
            mobileMenu.classList.toggle('active');
            document.body.style.overflow = mobileMenu.classList.contains('active') ? 'hidden' : '';
        });

        // Close menu when clicking a link
        mobileMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                menuToggle.classList.remove('active');
                mobileMenu.classList.remove('active');
                document.body.style.overflow = '';
            });
        });
    }

    // ========================================
    // Smooth Scroll for Anchor Links
    // ========================================
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href === '#') return;
            
            const target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                const navHeight = nav.offsetHeight;
                const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - navHeight;
                
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });

    // ========================================
    // Scroll Reveal Animation
    // ========================================
    const revealElements = document.querySelectorAll(
        '.collections-header, .collection-card, .how-header, .step, ' +
        '.testimonials-header, .testimonial-card, .faq-card, ' +
        '.newsletter, .footer-links'
    );

    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('active');
                revealObserver.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });

    revealElements.forEach((el, index) => {
        // Add stagger delay based on index for cards
        if (el.classList.contains('collection-card') || 
            el.classList.contains('testimonial-card') ||
            el.classList.contains('step')) {
            el.style.transitionDelay = `${index * 0.1}s`;
        }
        
        el.classList.add('reveal');
        revealObserver.observe(el);
    });

    // ========================================
    // Parallax Effect for Hero Cards
    // ========================================
    const heroCards = document.querySelectorAll('.hero-card');
    
    function handleParallax() {
        const scrolled = window.pageYOffset;
        const rate = scrolled * 0.3;
        
        heroCards.forEach((card, index) => {
            const direction = index % 2 === 0 ? 1 : -1;
            card.style.transform = `translateY(${rate * direction * 0.1}px)`;
        });
    }

    // Only apply parallax on non-touch devices
    if (!window.matchMedia('(pointer: coarse)').matches) {
        window.addEventListener('scroll', handleParallax, { passive: true });
    }

    // ========================================
    // Floating Animation for Gallery Cards
    // ========================================
    const galleryCards = document.querySelectorAll('.gallery-card');
    
    galleryCards.forEach((card, index) => {
        card.style.animation = `float 3s ease-in-out infinite`;
        card.style.animationDelay = `${index * 0.5}s`;
    });

    // ========================================
    // Toast Notification
    // ========================================
    function showToast(message) {
        const toastMessage = toast.querySelector('.toast-message');
        toastMessage.textContent = message;
        toast.classList.add('show');
        
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }

    // ========================================
    // Newsletter Form
    // ========================================
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = this.querySelector('input[type="email"]').value;
            
            if (email) {
                showToast('Thank you for subscribing!');
                this.reset();
            }
        });
    }

    // ========================================
    // Collection Cards Click Handler
    // ========================================
    document.querySelectorAll('.collection-card').forEach(card => {
        card.addEventListener('click', function() {
            showToast('Coming soon!');
        });
    });

    // ========================================
    // Button Click Handlers for Coming Soon
    // ========================================
    document.querySelectorAll('.btn-primary, .btn-secondary, .btn-accent').forEach(btn => {
        // Skip buttons that have proper links
        if (btn.getAttribute('href') && btn.getAttribute('href').startsWith('#')) {
            return;
        }
        
        btn.addEventListener('click', function(e) {
            if (!this.getAttribute('href') || this.getAttribute('href') === '#') {
                e.preventDefault();
                showToast('Coming soon!');
            }
        });
    });

    // ========================================
    // FAQ Accordion Animation
    // ========================================
    document.querySelectorAll('.faq-item').forEach(item => {
        const summary = item.querySelector('summary');
        const content = item.querySelector('p');
        
        summary.addEventListener('click', function(e) {
            e.preventDefault();
            const isOpen = item.hasAttribute('open');
            
            // Close all other items
            document.querySelectorAll('.faq-item').forEach(otherItem => {
                if (otherItem !== item && otherItem.hasAttribute('open')) {
                    otherItem.removeAttribute('open');
                }
            });
            
            if (isOpen) {
                item.removeAttribute('open');
            } else {
                item.setAttribute('open', '');
            }
        });
    });

    // ========================================
    // Gift Scene Scroll Animation
    // ========================================
    const giftScene = document.querySelector('.gift-scene');
    const giftCards = document.querySelectorAll('.gift-card');
    const giftText = document.querySelector('.gift-text');

    if (giftScene) {
        const giftObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    giftCards.forEach((card, index) => {
                        setTimeout(() => {
                            card.style.opacity = '1';
                            card.style.transform = 'translateY(0)';
                        }, index * 100);
                    });
                    
                    if (giftText) {
                        setTimeout(() => {
                            giftText.style.opacity = '1';
                            giftText.style.transform = 'translateY(-50%)';
                        }, 400);
                    }
                    
                    giftObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.2 });

        // Set initial state
        giftCards.forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(50px)';
            card.style.transition = 'all 0.6s ease';
        });
        
        if (giftText) {
            giftText.style.opacity = '0';
            giftText.style.transform = 'translateY(-30%)';
            giftText.style.transition = 'all 0.6s ease 0.4s';
        }
        
        giftObserver.observe(giftScene);
    }

    // ========================================
    // Closing CTA Scroll Animation
    // ========================================
    const closingCta = document.querySelector('.closing-cta');
    const closingCards = document.querySelectorAll('.closing-card');

    if (closingCta) {
        const closingObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    closingCards.forEach((card, index) => {
                        setTimeout(() => {
                            card.style.opacity = '1';
                            card.style.transform = 'translateX(0)';
                        }, index * 150);
                    });
                    
                    closingObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.2 });

        // Set initial state
        closingCards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateX(100px)';
            card.style.transition = 'all 0.7s ease';
        });
        
        closingObserver.observe(closingCta);
    }

    // ========================================
    // Bold Statement Section Animation
    // ========================================
    const boldStatement = document.querySelector('.bold-statement');
    const boldContent = document.querySelector('.bold-content');

    if (boldStatement && boldContent) {
        const boldObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    boldContent.style.opacity = '1';
                    boldContent.style.transform = 'translateY(0)';
                    boldObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.3 });

        boldContent.style.opacity = '0';
        boldContent.style.transform = 'translateY(40px)';
        boldContent.style.transition = 'all 0.8s ease';
        
        boldObserver.observe(boldStatement);
    }

    // ========================================
    // Floating Gallery Animation
    // ========================================
    const floatingGallery = document.querySelector('.floating-gallery');
    const galleryCardsAll = document.querySelectorAll('.gallery-card');

    if (floatingGallery) {
        const galleryObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    galleryCardsAll.forEach((card, index) => {
                        const direction = index === 0 ? -50 : index === 2 ? 50 : 0;
                        const rotate = index === 0 ? -6 : index === 2 ? 6 : 0;
                        
                        card.style.opacity = '0';
                        card.style.transform = `translateX(${direction}px) rotate(${rotate}deg)`;
                        
                        setTimeout(() => {
                            card.style.transition = 'all 0.7s ease';
                            card.style.opacity = '1';
                            card.style.transform = 'translateX(0) rotate(0)';
                        }, index * 150);
                    });
                    
                    galleryObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.2 });

        galleryObserver.observe(floatingGallery);
    }

    // ========================================
    // Prefers Reduced Motion
    // ========================================
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        // Disable all animations
        document.querySelectorAll('.hero-card, .gift-card, .gallery-card, .closing-card').forEach(el => {
            el.style.opacity = '1';
            el.style.transform = 'none';
            el.style.animation = 'none';
        });
        
        if (giftText) {
            giftText.style.opacity = '1';
            giftText.style.transform = 'translateY(-50%)';
        }
        
        if (boldContent) {
            boldContent.style.opacity = '1';
            boldContent.style.transform = 'none';
        }
    }

    // ========================================
    // Initialize
    // ========================================
    handleNavScroll();

    console.log('🍰 Sweets & Strings - Ready to delight!');
})();
