import "./index.css";

export default passedOptions => {
  const defaults = {
    appendTimestamp: false,
    keepAliveMethod: "POST",
    keepAliveUrl: "/keep-alive",
    logOutBtnText: "Log out now",
    logOutUrl: "/log-out",
    message: "Your session is about to expire.",
    stayConnectedBtnText: "Stay connected",
    timeOutAfter: 1200000,
    timeOutUrl: "/timed-out",
    titleText: "Session Timetout",
    warnAfter: 900000
  };

  const options = Object.assign(defaults, passedOptions);

  let warnTimer;
  let timeOutTimer;

  const container = document.createElement("div");
  const modal = document.createElement("div");
  const content = document.createElement("div");
  const title = document.createElement("div");
  const buttons = document.createElement("div");
  const logOutBtn = document.createElement("button");
  const stayConnectedBtn = document.createElement("button");

  const warn = () => {
    container.classList.remove("sessionTimeout--hidden");
    clearTimeout(warnTimer);
  };

  const timeOut = () => {
    window.location = options.timeOutUrl;
  };

  const logOut = () => {
    window.location = options.logOutUrl;
  };

  const stayConnected = () => {
    container.classList.add("sessionTimeout--hidden");

    const url = options.appendTimestamp
      ? `${options.keepAliveUrl}?time=${Date.now()}`
      : options.keepAliveUrl;
    const req = new XMLHttpRequest();
    req.open(options.keepAliveMethod, url);
    req.send();

    warnTimer = setTimeout(warn, options.warnAfter);
    clearTimeout(timeOutTimer);
    timeOutTimer = setTimeout(timeOut, options.timeOutAfter);
  };

  logOutBtn.addEventListener("click", logOut);
  stayConnectedBtn.addEventListener("click", stayConnected);

  container.classList.add("sessionTimeout", "sessionTimeout--hidden");
  modal.classList.add("sessionTimeout-modal");
  title.classList.add("sessionTimeout-title");
  content.classList.add("sessionTimeout-content");
  buttons.classList.add("sessionTimeout-buttons");
  logOutBtn.classList.add(
    "sessionTimeout-btn",
    "sessionTimeout-btn--secondary"
  );
  stayConnectedBtn.classList.add(
    "sessionTimeout-btn",
    "sessionTimeout-btn--primary"
  );

  title.innerText = options.titleText;
  content.innerText = options.message;
  logOutBtn.innerText = options.logOutBtnText;
  stayConnectedBtn.innerText = options.stayConnectedBtnText;

  modal.appendChild(title);
  modal.appendChild(content);
  modal.appendChild(buttons);
  buttons.appendChild(logOutBtn);
  buttons.appendChild(stayConnectedBtn);
  container.appendChild(modal);
  document.body.appendChild(container);

  warnTimer = setTimeout(warn, options.warnAfter);
  timeOutTimer = setTimeout(timeOut, options.timeOutAfter);
};
