"use strict";

(() => {
  const root = document.getElementById("menu-main");
  const grid = document.getElementById("menu-grid");
  const navList = document.getElementById("category-nav-list");
  const status = document.getElementById("menu-status");
  const errorPanel = document.getElementById("menu-error");
  const emptyPanel = document.getElementById("menu-empty");

  if (!(root instanceof HTMLElement) || !(grid instanceof HTMLElement)) {
    return;
  }

  const endpoint = root.dataset.menuEndpoint || "api/menu.php";
  const numberFormat = new Intl.NumberFormat("fa-IR");

  let lastGeneratedAt = root.dataset.generatedAt || "";
  let isRefreshing = false;

  const toAnchorId = (slug) => {
    const normalized = String(slug || "")
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, "-")
      .replace(/^-+|-+$/g, "");
    return normalized === "" ? "category" : `category-${normalized}`;
  };

  const toPriceText = (product) => {
    if (product.is_available !== true) {
      return "ناموجود";
    }
    if (typeof product.price_toman !== "number" || Number.isNaN(product.price_toman)) {
      return "ناموجود";
    }
    return `${numberFormat.format(product.price_toman)} تومان`;
  };

  const setStatus = (state) => {
    if (status instanceof HTMLElement) {
      status.dataset.state = state;
    }
  };

  const renderCategory = (category, index) => {
    const section = document.createElement("section");
    section.className = "menu-section";
    section.id = toAnchorId(category.slug);
    const titleId = `category-title-${index}`;
    section.setAttribute("aria-labelledby", titleId);

    const head = document.createElement("div");
    head.className = "menu-section__head";

    const title = document.createElement("h2");
    title.className = "menu-section__title";
    title.id = titleId;
    title.textContent = category.name || "";

    const count = document.createElement("span");
    count.className = "menu-section__count";
    count.textContent = `${numberFormat.format(category.products.length)} قلم`;

    head.append(title, count);
    section.append(head);

    const list = document.createElement("ul");
    list.className = "menu-list";

    category.products.forEach((product) => {
      const item = document.createElement("li");
      item.className = "menu-item";
      if (product.is_available !== true) {
        item.classList.add("menu-item--unavailable");
      }

      const name = document.createElement("span");
      name.className = "menu-item__name";
      name.textContent = product.name || "";

      const leader = document.createElement("span");
      leader.className = "menu-item__leader";
      leader.setAttribute("aria-hidden", "true");

      const price = document.createElement("span");
      price.className = "menu-item__price";
      price.textContent = toPriceText(product);

      item.append(name, leader, price);

      const description = typeof product.description === "string" ? product.description.trim() : "";
      if (description !== "") {
        const details = document.createElement("p");
        details.className = "menu-item__description";
        details.textContent = description;
        item.append(details);
      }

      list.append(item);
    });

    section.append(list);
    return section;
  };

  const renderMenu = (payload) => {
    const categories = Array.isArray(payload.categories)
      ? payload.categories.map((category) => ({
          id: Number(category.id),
          name: String(category.name || ""),
          slug: String(category.slug || ""),
          products: Array.isArray(category.products)
            ? category.products.map((product) => ({
                id: Number(product.id),
                name: String(product.name || ""),
                description: typeof product.description === "string" ? product.description : "",
                price_toman: typeof product.price_toman === "number" ? product.price_toman : null,
                is_available: product.is_available === true
              }))
            : []
        }))
      : [];

    grid.replaceChildren();
    categories.forEach((category, index) => {
      grid.append(renderCategory(category, index));
    });

    if (navList instanceof HTMLElement) {
      navList.replaceChildren();
      categories.forEach((category) => {
        const navItem = document.createElement("li");
        const link = document.createElement("a");
        link.className = "category-nav__link";
        link.href = `#${toAnchorId(category.slug)}`;
        link.textContent = category.name;
        navItem.append(link);
        navList.append(navItem);
      });
      const nav = navList.closest(".category-nav");
      if (nav instanceof HTMLElement) {
        nav.hidden = categories.length === 0;
      }
    }

    return categories.length;
  };

  const showError = (message) => {
    if (!(errorPanel instanceof HTMLElement)) {
      return;
    }
    errorPanel.hidden = false;
    errorPanel.replaceChildren();
    const title = document.createElement("h2");
    title.className = "notice__title";
    title.textContent = "خطا در خواندن منو";
    const text = document.createElement("p");
    text.textContent = `${message} آخرین قیمت های موفق همچنان نمایش داده می شود.`;
    errorPanel.append(title, text);
  };

  const showEmpty = (isEmpty) => {
    if (!(emptyPanel instanceof HTMLElement)) {
      return;
    }
    emptyPanel.hidden = !isEmpty;
    emptyPanel.replaceChildren();
    if (isEmpty) {
      const title = document.createElement("h2");
      title.className = "notice__title";
      title.textContent = "منویی ثبت نشده است";
      const text = document.createElement("p");
      text.textContent = "هنوز هیچ دسته فعالی در پایگاه داده وجود ندارد.";
      emptyPanel.append(title, text);
    }
  };

  const refresh = async ({ silent = true } = {}) => {
    if (isRefreshing || !navigator.onLine) {
      if (!navigator.onLine) {
        setStatus("offline");
      }
      return;
    }

    isRefreshing = true;
    if (!silent) {
      setStatus("checking");
    }

    try {
      const response = await fetch(endpoint, { headers: { Accept: "application/json" }, cache: "no-store" });
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }
      const payload = await response.json();
      if (!payload || payload.ok !== true || !Array.isArray(payload.categories)) {
        throw new Error("invalid menu payload");
      }

      if (payload.generated_at && payload.generated_at !== lastGeneratedAt) {
        const count = renderMenu(payload);
        lastGeneratedAt = payload.generated_at;
        root.dataset.generatedAt = lastGeneratedAt;
        if (errorPanel instanceof HTMLElement) {
          errorPanel.hidden = true;
        }
        showEmpty(count === 0);
      } else if (grid.childElementCount === 0) {
        const count = renderMenu(payload);
        showEmpty(count === 0);
      }

      setStatus("live");
    } catch (error) {
      if (grid.childElementCount > 0) {
        setStatus("offline");
      } else {
        setStatus("error");
        showError("در حال حاضر امکان خواندن منو وجود ندارد.");
      }
    } finally {
      isRefreshing = false;
    }
  };

  window.addEventListener("online", () => refresh({ silent: false }));
  window.addEventListener("offline", () => {
    setStatus("offline");
  });
  document.addEventListener("visibilitychange", () => {
    if (!document.hidden) {
      refresh();
    }
  });

  window.setInterval(refresh, 30000);
})();