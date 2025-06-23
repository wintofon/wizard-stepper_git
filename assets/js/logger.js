(function (global) {
  const PREFIX = '[WizardStepper]';
  const ts = () => new Date().toISOString();
  const active = () => !!global.DEBUG;
  const wrap = method => (...args) => {
    if (active()) {
      console[method](PREFIX, ts(), ...args);
    }
  };
  const Logger = {
    log: wrap('log'),
    warn: wrap('warn'),
    error: wrap('error'),
    table: data => { if (active()) console.table(data); },
    group(title) {
      if (!active()) return () => {};
      console.group(`${PREFIX} ${title} @ ${ts()}`);
      return () => console.groupEnd();
    }
  };
  global.Logger = Logger;
})(typeof window !== 'undefined' ? window : this);
export default window.Logger;
