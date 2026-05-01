var currentComments = [];

document.addEventListener("DOMContentLoaded", function () {

    const form = document.getElementById("comment-form");

    if (form) {

        form.addEventListener("submit", handleAddComment);

    }

    initializePage();

});

function getResourceIdFromURL() {

    const params = new URLSearchParams(window.location.search);

    return params.get("id");

}

function renderResourceDetails(resource) {

    document.getElementById("resource-title").textContent = resource.title;

    document.getElementById("resource-description").textContent = resource.description;

    document.getElementById("resource-link").setAttribute("href", resource.link);

}

function createCommentArticle(comment) {

    const article = document.createElement("article");

    const p = document.createElement("p");

    p.textContent = comment.text || comment.comment || "";

    const footer = document.createElement("footer");

    footer.textContent = comment.author || ("User " + (comment.user_id || ""));

    article.appendChild(p);

    article.appendChild(footer);

    return article;

}

function renderComments() {

    const list = document.getElementById("comment-list");

    if (!list) {

        return;

    }

    list.innerHTML = "";

    currentComments.forEach(function (comment) {

        list.appendChild(createCommentArticle(comment));

    });

}

function handleAddComment(event) {

    event.preventDefault();

    const textarea = document.getElementById("new-comment");

    const text = textarea.value.trim();

    if (text === "") {

        return;

    }

    const resourceId = getResourceIdFromURL();

    fetch("./api/index.php?action=comment", {

        method: "POST",

        headers: {

            "Content-Type": "application/json"

        },

        body: JSON.stringify({

            resource_id: resourceId,

            text: text,

            comment: text

        })

    })

    .then(function (response) {

        return response.json();

    })

    .then(function (result) {

        textarea.value = "";

        if (result.data) {

            currentComments.push(result.data);

            renderComments();

        }

    });

}

async function initializePage() {

    const id = getResourceIdFromURL();

    if (!id) {

        return;

    }

    const resourceResponse = await fetch("./api/index.php?id=" + encodeURIComponent(id));

    const resourceResult = await resourceResponse.json();

    if (resourceResult.data) {

        renderResourceDetails(resourceResult.data);

    } else if (resourceResult.resource) {

        renderResourceDetails(resourceResult.resource);

    }

    const commentsResponse = await fetch("./api/index.php?action=comments&resource_id=" + encodeURIComponent(id));

    const commentsResult = await commentsResponse.json();

    currentComments = commentsResult.data || commentsResult.comments || [];

    renderComments();

}
