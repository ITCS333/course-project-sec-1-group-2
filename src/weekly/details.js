// --- Global Data Store ---
let currentWeekId  = null;
let currentComments = [];

// --- Element Selections ---
const weekTitle = document.getElementById('week-title');
const weekStartDate = document.getElementById('week-start-date');
const weekDescription = document.getElementById('week-description');
const weekLinksList = document.getElementById('week-links-list');
const commentList = document.getElementById('comment-list');
const commentForm = document.getElementById('comment-form');
const newCommentInput = document.getElementById('new-comment');

// --- Functions ---

// git id from URL
function getWeekIdFromURL() {
    const params = new URLSearchParams(window.location.search);
    return params.get('id');
}

// view week details
function renderWeekDetails(week) {
    weekTitle.textContent = week.title;
    weekStartDate.textContent = "Starts on: " + week.start_date;
    weekDescription.textContent = week.description;

    weekLinksList.innerHTML = '';

    week.links.forEach(url => {
        const li = document.createElement('li');
        li.className = "list-group-item";

        const a = document.createElement('a');
        a.href = url;
        a.textContent = url;
        a.target = "_blank";

        li.appendChild(a);
        weekLinksList.appendChild(li);
    });
}

//  creat comment
function createCommentArticle(comment) {
    const article = document.createElement('article');
    article.className = "border p-3 mb-2 rounded";

    const p = document.createElement('p');
    p.textContent = comment.text;

    const footer = document.createElement('footer');
    footer.textContent = "Posted by: " + comment.author;

    article.appendChild(p);
    article.appendChild(footer);

    return article;
}

// view comment 
function renderComments() {
    commentList.innerHTML = '';

    currentComments.forEach(comment => {
        commentList.appendChild(createCommentArticle(comment));
    });
}

// add comment  
async function handleAddComment(event) {
    event.preventDefault();

    const commentText = newCommentInput.value.trim();
    if (!commentText) return;

    try {
        const response = await fetch('./api/index.php?action=comment', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                week_id: parseInt(currentWeekId),
                author: "Student",
                text: commentText
            })
        });

        const result = await response.json();

        if (result.success) {
            currentComments.push(result.data);
            renderComments();
            newCommentInput.value = '';
        } else {
            alert('Failed to add comment');
        }
    } catch (err) {
        console.error(err);
        alert('Error adding comment');
    }
}

// load page  
async function initializePage() {
    currentWeekId = getWeekIdFromURL();

    if (!currentWeekId) {
        weekTitle.textContent = "Week not found.";
        return;
    }

    try {
        const [weekRes, commentsRes] = await Promise.all([
            fetch(`./api/index.php?id=${currentWeekId}`),
            fetch(`./api/index.php?action=comments&week_id=${currentWeekId}`)
        ]);

        const weekResult = await weekRes.json();
        const commentsResult = await commentsRes.json();

        if (weekResult.success && weekResult.data) {
            renderWeekDetails(weekResult.data);
        } else {
            weekTitle.textContent = "Week not found.";
            return;
        }

        currentComments = commentsResult.success ? commentsResult.data : [];
        renderComments();

        commentForm.addEventListener('submit', handleAddComment);

    } catch (err) {
        console.error(err);
        weekTitle.textContent = "Error loading data.";
    }
}

// --- Initial Page Load ---
initializePage();
