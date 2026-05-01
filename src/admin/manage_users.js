let users = [];
let listenersAttached = false;

function getUserTableBody() {
  return typeof document !== "undefined"
    ? document.getElementById("user-table-body")
    : null;
}

function getAddUserForm() {
  return typeof document !== "undefined"
    ? document.getElementById("add-user-form")
    : null;
}

function getChangePasswordForm() {
  return typeof document !== "undefined"
    ? document.getElementById("password-form")
    : null;
}

function getSearchInput() {
  return typeof document !== "undefined"
    ? document.getElementById("search-input")
    : null;
}

function getTableHeaders() {
  return typeof document !== "undefined"
    ? document.querySelectorAll("#user-table thead th")
    : [];
}

function createUserRow(user) {
  const tr = document.createElement("tr");

  const nameTd = document.createElement("td");
  nameTd.textContent = user.name;

  const emailTd = document.createElement("td");
  emailTd.textContent = user.email;

  const adminTd = document.createElement("td");
  adminTd.textContent = user.is_admin == 1 ? "Yes" : "No";

  const actionsTd = document.createElement("td");

  const editBtn = document.createElement("button");
  editBtn.textContent = "Edit";
  editBtn.className = "edit-btn";
  editBtn.dataset.id = user.id;

  const deleteBtn = document.createElement("button");
  deleteBtn.textContent = "Delete";
  deleteBtn.className = "delete-btn";
  deleteBtn.dataset.id = user.id;

  actionsTd.appendChild(editBtn);
  actionsTd.appendChild(deleteBtn);

  tr.appendChild(nameTd);
  tr.appendChild(emailTd);
  tr.appendChild(adminTd);
  tr.appendChild(actionsTd);

  return tr;
}

function renderTable(userArray) {
  const userTableBody = getUserTableBody();
  if (!userTableBody) return;

  userTableBody.innerHTML = "";

  userArray.forEach((user) => {
    const row = createUserRow(user);
    userTableBody.appendChild(row);
  });
}

async function handleChangePassword(event) {
  event.preventDefault();

  const currentPasswordInput = document.getElementById("current-password");
  const newPasswordInput = document.getElementById("new-password");
  const confirmPasswordInput = document.getElementById("confirm-password");

  const currentPassword = currentPasswordInput.value;
  const newPassword = newPasswordInput.value;
  const confirmPassword = confirmPasswordInput.value;

  if (newPassword !== confirmPassword) {
    alert("Passwords do not match.");
    return;
  }

  if (newPassword.length < 8) {
    alert("Password must be at least 8 characters.");
    return;
  }

  currentPasswordInput.value = "";
  newPasswordInput.value = "";
  confirmPasswordInput.value = "";

  const response = await fetch("../api/index.php?action=change_password", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({
      id: 1,
      current_password: currentPassword,
      new_password: newPassword
    })
  });

  const result = await response.json();

  if (result.success) {
    alert("Password updated successfully!");
  } else {
    alert(result.message);
  }
}

async function handleAddUser(event) {
  event.preventDefault();

  const name = document.getElementById("user-name").value;
  const email = document.getElementById("user-email").value;
  const password = document.getElementById("default-password").value;
  const is_admin = document.getElementById("is-admin").value;

  if (!name || !email || !password) {
    alert("Please fill out all required fields.");
    return;
  }

  if (password.length < 8) {
    alert("Password must be at least 8 characters.");
    return;
  }

  const response = await fetch("../api/index.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({
      name,
      email,
      password,
      is_admin
    })
  });

  if (response.status === 201) {
    await loadUsersAndInitialize();
    const addUserForm = getAddUserForm();
    if (addUserForm) addUserForm.reset();
  } else {
    const result = await response.json();
    alert(result.message);
  }
}

async function handleTableClick(event) {
  if (event.target.classList.contains("delete-btn")) {
    const id = event.target.dataset.id;

    const response = await fetch("../api/index.php?id=" + id, {
      method: "DELETE"
    });

    const result = await response.json();

    if (result.success) {
      users = users.filter((user) => user.id != id);
      renderTable(users);
    } else {
      alert(result.message);
    }
  }

  if (event.target.classList.contains("edit-btn")) {
    const id = event.target.dataset.id;
    const user = users.find((u) => u.id == id);

    const newName = prompt("Enter new name:", user.name);
    if (!newName) return;

    const response = await fetch("../api/index.php", {
      method: "PUT",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({
        id,
        name: newName
      })
    });

    const result = await response.json();

    if (result.success) {
      await loadUsersAndInitialize();
    } else {
      alert(result.message);
    }
  }
}

function handleSearch() {
  const searchInput = getSearchInput();
  if (!searchInput) return;

  const term = searchInput.value.toLowerCase();

  if (!term) {
    renderTable(users);
    return;
  }

  const filtered = users.filter(
    (user) =>
      user.name.toLowerCase().includes(term) ||
      user.email.toLowerCase().includes(term)
  );

  renderTable(filtered);
}

function handleSort(event) {
  const index = event.currentTarget.cellIndex;
  const mapping = ["name", "email", "is_admin"];
  const key = mapping[index];

  if (!key) return;

  const currentDir = event.currentTarget.dataset.sortDir || "none";
  const newDir = currentDir === "asc" ? "desc" : "asc";
  event.currentTarget.dataset.sortDir = newDir;

  users.sort((a, b) => {
    let result;

    if (key === "is_admin") {
      result = Number(a[key]) - Number(b[key]);
    } else {
      result = a[key].localeCompare(b[key]);
    }

    return newDir === "asc" ? result : -result;
  });

  renderTable(users);
}

async function loadUsersAndInitialize() {
  const response = await fetch("../api/index.php");

  if (!response.ok) {
    alert("Failed to load users.");
    return;
  }

  const result = await response.json();
  users = result.data;
  renderTable(users);

  if (!listenersAttached) {
    const changePasswordForm = getChangePasswordForm();
    const addUserForm = getAddUserForm();
    const userTableBody = getUserTableBody();
    const searchInput = getSearchInput();
    const tableHeaders = getTableHeaders();

    if (changePasswordForm) {
      changePasswordForm.addEventListener("submit", handleChangePassword);
    }

    if (addUserForm) {
      addUserForm.addEventListener("submit", handleAddUser);
    }

    if (userTableBody) {
      userTableBody.addEventListener("click", handleTableClick);
    }

    if (searchInput) {
      searchInput.addEventListener("input", handleSearch);
    }

    tableHeaders.forEach((th) => {
      th.addEventListener("click", handleSort);
    });

    listenersAttached = true;
  }
}

if (typeof window !== "undefined" && typeof document !== "undefined") {
  loadUsersAndInitialize();
}

if (typeof module !== "undefined" && module.exports) {
  module.exports = {
    createUserRow,
    renderTable,
    handleChangePassword,
    handleAddUser,
    handleTableClick,
    handleSearch,
    handleSort,
    loadUsersAndInitialize
  };
}
