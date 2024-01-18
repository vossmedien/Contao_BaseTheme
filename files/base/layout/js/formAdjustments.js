// formAdjustments.js
// Modul für die Anpassung von Formularelementen

/**
 * Passt das Layout von Formularen an.
 */
export function adjustFormLayout() {
  // Anpassung des Layouts der Formulare
  document
    .querySelectorAll("form .formbody:not(.no-row) > fieldset")
    .forEach((el) => {
      el.classList.add("row");
      const wrapper = document.createElement("div");
      wrapper.className = "col-12";
      el.parentNode.insertBefore(wrapper, el);
      wrapper.appendChild(el);
    });

  document
    .querySelectorAll(
      "form:not(#iso_mod_checkout_review) > .formbody:not(.row):not(.no-row)"
    )
    .forEach((el) => {
      el.classList.add("row");
    });

  document.querySelectorAll("form .formbody .address_new").forEach((el) => {
    el.classList.add("row");
  });

  document.querySelectorAll("form .formbody  p.alert").forEach((el) => {
    el.classList.add("mt-0");
    let wrapper = document.createElement("div");
    wrapper.className = "col-12";
    el.parentNode.insertBefore(wrapper, el);
    wrapper.appendChild(el);
  });

  document.querySelectorAll("form .widget.form-control").forEach((el) => {
    el.classList.remove("form-control");
  });

  document.querySelectorAll("form label:not(.form-label)").forEach((label) => {
    label.className = "";
  });

  let formBodies = document.querySelectorAll("form > .formbody:not(.no-row)");
  formBodies.forEach(function (formBody) {
    let elements = formBody.querySelectorAll(':scope > *:not([class^="col-"])');
    elements.forEach(function (element) {
      element.classList.add("col-12");
    });
  });

  let formFieldsetBodies = document.querySelectorAll(
    "form > .formbody fieldset"
  );
  formFieldsetBodies.forEach(function (FormFieldset) {
    let elements = FormFieldset.querySelectorAll(
      ':scope > *:not([class^="col-"])'
    );
    elements.forEach(function (element) {
      element.classList.add("col-12");
    });
  });

  document.querySelectorAll(".widget.widget-submit").forEach((widget) => {
    Array.from(widget.classList).forEach((cls) => {
      if (cls.startsWith("btn")) {
        widget.classList.remove(cls);
      }
    });
  });

  document
    .querySelectorAll("form .formbody > .fields")
    .forEach((fieldsContainer) => {
      const elements = Array.from(fieldsContainer.children);
      elements.forEach((el) =>
        fieldsContainer.parentNode.insertBefore(el, fieldsContainer)
      );
      fieldsContainer.parentNode.removeChild(fieldsContainer);
    });
}
