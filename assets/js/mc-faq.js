document.addEventListener('DOMContentLoaded', () => {
    // FAQ Accordion functionality
    const faqItems = document.querySelectorAll('.mc-faq-question');
    faqItems.forEach(item => {
        item.addEventListener('click', () => {
            const answer = item.nextElementSibling;
            const isActive = item.classList.contains('active');

            // Close all other FAQs
            document.querySelectorAll('.mc-faq-question.active').forEach(activeItem => {
                if (activeItem !== item) {
                    activeItem.classList.remove('active');
                    activeItem.nextElementSibling.classList.remove('active');
                }
            });

            // Toggle current FAQ
            item.classList.toggle('active');
            answer.classList.toggle('active');

            // Smooth scroll if opening
            if (!isActive) {
                item.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // FAQ Search functionality
    const searchInput = document.querySelector('.mc-faq-search input');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const faqItems = document.querySelectorAll('.mc-faq-item');

            faqItems.forEach(item => {
                const question = item.querySelector('.mc-faq-question').textContent.toLowerCase();
                const answer = item.querySelector('.mc-faq-answer').textContent.toLowerCase();
                
                if (question.includes(searchTerm) || answer.includes(searchTerm)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }

    // FAQ Category filtering
    const categoryButtons = document.querySelectorAll('.mc-faq-category');
    categoryButtons.forEach(button => {
        button.addEventListener('click', () => {
            const category = button.dataset.category;
            
            // Toggle active state
            categoryButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');

            // Filter FAQs
            const faqItems = document.querySelectorAll('.mc-faq-item');
            faqItems.forEach(item => {
                if (category === 'all' || item.dataset.categories.includes(category)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
});
