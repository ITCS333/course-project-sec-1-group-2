// --- Element Selections ---
const weekListSection = document.getElementById('week-list-section');

// --- Functions ---

// 1️⃣ Create Week Article
function createWeekArticle(week) {
    const article = document.createElement('article');
    article.className = "col-md-6";

    const card = document.createElement('div');
    card.className = "card p-3 h-100";

    const title = document.createElement('h2');
    title.textContent = week.title;

    const date = document.createElement('p');
    date.className = "text-muted";
    date.textContent = "Starts on: " + week.start_date;

    const description = document.createElement('p');
    description.textContent = week.description;

    const link = document.createElement('a');
    link.href = `details.html?id=${week.id}`;
    link.textContent = "View Details & Discussion";
    link.className = "btn btn-primary mt-auto";

    
    card.appendChild(title);
    card.appendChild(date);
    card.appendChild(description);
    card.appendChild(link);

    article.appendChild(card);

    return article;
}

// 2️⃣ Load Weeks
async function loadWeeks() {
    try {
        const response = await fetch('./api/index.php');
        const result = await response.json();

        if (result.success) {
            weekListSection.innerHTML = '';

            result.data.forEach(week => {
                const article = createWeekArticle(week);
                weekListSection.appendChild(article);
            });

        } else {
            weekListSection.innerHTML = "<p>Failed to load weeks.</p>";
        }

    } catch (error) {
        console.error(error);
        weekListSection.innerHTML = "<p>Error loading data.</p>";
    }
}

// --- Initial Page Load ---
loadWeeks();
