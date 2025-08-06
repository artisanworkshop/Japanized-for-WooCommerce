/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./node_modules/react/cjs/react-jsx-runtime.development.js":
/*!*****************************************************************!*\
  !*** ./node_modules/react/cjs/react-jsx-runtime.development.js ***!
  \*****************************************************************/
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {

/**
 * @license React
 * react-jsx-runtime.development.js
 *
 * Copyright (c) Facebook, Inc. and its affiliates.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */



if (true) {
  (function() {
'use strict';

var React = __webpack_require__(/*! react */ "react");

// ATTENTION
// When adding new symbols to this file,
// Please consider also adding to 'react-devtools-shared/src/backend/ReactSymbols'
// The Symbol used to tag the ReactElement-like types.
var REACT_ELEMENT_TYPE = Symbol.for('react.element');
var REACT_PORTAL_TYPE = Symbol.for('react.portal');
var REACT_FRAGMENT_TYPE = Symbol.for('react.fragment');
var REACT_STRICT_MODE_TYPE = Symbol.for('react.strict_mode');
var REACT_PROFILER_TYPE = Symbol.for('react.profiler');
var REACT_PROVIDER_TYPE = Symbol.for('react.provider');
var REACT_CONTEXT_TYPE = Symbol.for('react.context');
var REACT_FORWARD_REF_TYPE = Symbol.for('react.forward_ref');
var REACT_SUSPENSE_TYPE = Symbol.for('react.suspense');
var REACT_SUSPENSE_LIST_TYPE = Symbol.for('react.suspense_list');
var REACT_MEMO_TYPE = Symbol.for('react.memo');
var REACT_LAZY_TYPE = Symbol.for('react.lazy');
var REACT_OFFSCREEN_TYPE = Symbol.for('react.offscreen');
var MAYBE_ITERATOR_SYMBOL = Symbol.iterator;
var FAUX_ITERATOR_SYMBOL = '@@iterator';
function getIteratorFn(maybeIterable) {
  if (maybeIterable === null || typeof maybeIterable !== 'object') {
    return null;
  }

  var maybeIterator = MAYBE_ITERATOR_SYMBOL && maybeIterable[MAYBE_ITERATOR_SYMBOL] || maybeIterable[FAUX_ITERATOR_SYMBOL];

  if (typeof maybeIterator === 'function') {
    return maybeIterator;
  }

  return null;
}

var ReactSharedInternals = React.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED;

function error(format) {
  {
    {
      for (var _len2 = arguments.length, args = new Array(_len2 > 1 ? _len2 - 1 : 0), _key2 = 1; _key2 < _len2; _key2++) {
        args[_key2 - 1] = arguments[_key2];
      }

      printWarning('error', format, args);
    }
  }
}

function printWarning(level, format, args) {
  // When changing this logic, you might want to also
  // update consoleWithStackDev.www.js as well.
  {
    var ReactDebugCurrentFrame = ReactSharedInternals.ReactDebugCurrentFrame;
    var stack = ReactDebugCurrentFrame.getStackAddendum();

    if (stack !== '') {
      format += '%s';
      args = args.concat([stack]);
    } // eslint-disable-next-line react-internal/safe-string-coercion


    var argsWithFormat = args.map(function (item) {
      return String(item);
    }); // Careful: RN currently depends on this prefix

    argsWithFormat.unshift('Warning: ' + format); // We intentionally don't use spread (or .apply) directly because it
    // breaks IE9: https://github.com/facebook/react/issues/13610
    // eslint-disable-next-line react-internal/no-production-logging

    Function.prototype.apply.call(console[level], console, argsWithFormat);
  }
}

// -----------------------------------------------------------------------------

var enableScopeAPI = false; // Experimental Create Event Handle API.
var enableCacheElement = false;
var enableTransitionTracing = false; // No known bugs, but needs performance testing

var enableLegacyHidden = false; // Enables unstable_avoidThisFallback feature in Fiber
// stuff. Intended to enable React core members to more easily debug scheduling
// issues in DEV builds.

var enableDebugTracing = false; // Track which Fiber(s) schedule render work.

var REACT_MODULE_REFERENCE;

{
  REACT_MODULE_REFERENCE = Symbol.for('react.module.reference');
}

function isValidElementType(type) {
  if (typeof type === 'string' || typeof type === 'function') {
    return true;
  } // Note: typeof might be other than 'symbol' or 'number' (e.g. if it's a polyfill).


  if (type === REACT_FRAGMENT_TYPE || type === REACT_PROFILER_TYPE || enableDebugTracing  || type === REACT_STRICT_MODE_TYPE || type === REACT_SUSPENSE_TYPE || type === REACT_SUSPENSE_LIST_TYPE || enableLegacyHidden  || type === REACT_OFFSCREEN_TYPE || enableScopeAPI  || enableCacheElement  || enableTransitionTracing ) {
    return true;
  }

  if (typeof type === 'object' && type !== null) {
    if (type.$$typeof === REACT_LAZY_TYPE || type.$$typeof === REACT_MEMO_TYPE || type.$$typeof === REACT_PROVIDER_TYPE || type.$$typeof === REACT_CONTEXT_TYPE || type.$$typeof === REACT_FORWARD_REF_TYPE || // This needs to include all possible module reference object
    // types supported by any Flight configuration anywhere since
    // we don't know which Flight build this will end up being used
    // with.
    type.$$typeof === REACT_MODULE_REFERENCE || type.getModuleId !== undefined) {
      return true;
    }
  }

  return false;
}

function getWrappedName(outerType, innerType, wrapperName) {
  var displayName = outerType.displayName;

  if (displayName) {
    return displayName;
  }

  var functionName = innerType.displayName || innerType.name || '';
  return functionName !== '' ? wrapperName + "(" + functionName + ")" : wrapperName;
} // Keep in sync with react-reconciler/getComponentNameFromFiber


function getContextName(type) {
  return type.displayName || 'Context';
} // Note that the reconciler package should generally prefer to use getComponentNameFromFiber() instead.


function getComponentNameFromType(type) {
  if (type == null) {
    // Host root, text node or just invalid type.
    return null;
  }

  {
    if (typeof type.tag === 'number') {
      error('Received an unexpected object in getComponentNameFromType(). ' + 'This is likely a bug in React. Please file an issue.');
    }
  }

  if (typeof type === 'function') {
    return type.displayName || type.name || null;
  }

  if (typeof type === 'string') {
    return type;
  }

  switch (type) {
    case REACT_FRAGMENT_TYPE:
      return 'Fragment';

    case REACT_PORTAL_TYPE:
      return 'Portal';

    case REACT_PROFILER_TYPE:
      return 'Profiler';

    case REACT_STRICT_MODE_TYPE:
      return 'StrictMode';

    case REACT_SUSPENSE_TYPE:
      return 'Suspense';

    case REACT_SUSPENSE_LIST_TYPE:
      return 'SuspenseList';

  }

  if (typeof type === 'object') {
    switch (type.$$typeof) {
      case REACT_CONTEXT_TYPE:
        var context = type;
        return getContextName(context) + '.Consumer';

      case REACT_PROVIDER_TYPE:
        var provider = type;
        return getContextName(provider._context) + '.Provider';

      case REACT_FORWARD_REF_TYPE:
        return getWrappedName(type, type.render, 'ForwardRef');

      case REACT_MEMO_TYPE:
        var outerName = type.displayName || null;

        if (outerName !== null) {
          return outerName;
        }

        return getComponentNameFromType(type.type) || 'Memo';

      case REACT_LAZY_TYPE:
        {
          var lazyComponent = type;
          var payload = lazyComponent._payload;
          var init = lazyComponent._init;

          try {
            return getComponentNameFromType(init(payload));
          } catch (x) {
            return null;
          }
        }

      // eslint-disable-next-line no-fallthrough
    }
  }

  return null;
}

var assign = Object.assign;

// Helpers to patch console.logs to avoid logging during side-effect free
// replaying on render function. This currently only patches the object
// lazily which won't cover if the log function was extracted eagerly.
// We could also eagerly patch the method.
var disabledDepth = 0;
var prevLog;
var prevInfo;
var prevWarn;
var prevError;
var prevGroup;
var prevGroupCollapsed;
var prevGroupEnd;

function disabledLog() {}

disabledLog.__reactDisabledLog = true;
function disableLogs() {
  {
    if (disabledDepth === 0) {
      /* eslint-disable react-internal/no-production-logging */
      prevLog = console.log;
      prevInfo = console.info;
      prevWarn = console.warn;
      prevError = console.error;
      prevGroup = console.group;
      prevGroupCollapsed = console.groupCollapsed;
      prevGroupEnd = console.groupEnd; // https://github.com/facebook/react/issues/19099

      var props = {
        configurable: true,
        enumerable: true,
        value: disabledLog,
        writable: true
      }; // $FlowFixMe Flow thinks console is immutable.

      Object.defineProperties(console, {
        info: props,
        log: props,
        warn: props,
        error: props,
        group: props,
        groupCollapsed: props,
        groupEnd: props
      });
      /* eslint-enable react-internal/no-production-logging */
    }

    disabledDepth++;
  }
}
function reenableLogs() {
  {
    disabledDepth--;

    if (disabledDepth === 0) {
      /* eslint-disable react-internal/no-production-logging */
      var props = {
        configurable: true,
        enumerable: true,
        writable: true
      }; // $FlowFixMe Flow thinks console is immutable.

      Object.defineProperties(console, {
        log: assign({}, props, {
          value: prevLog
        }),
        info: assign({}, props, {
          value: prevInfo
        }),
        warn: assign({}, props, {
          value: prevWarn
        }),
        error: assign({}, props, {
          value: prevError
        }),
        group: assign({}, props, {
          value: prevGroup
        }),
        groupCollapsed: assign({}, props, {
          value: prevGroupCollapsed
        }),
        groupEnd: assign({}, props, {
          value: prevGroupEnd
        })
      });
      /* eslint-enable react-internal/no-production-logging */
    }

    if (disabledDepth < 0) {
      error('disabledDepth fell below zero. ' + 'This is a bug in React. Please file an issue.');
    }
  }
}

var ReactCurrentDispatcher = ReactSharedInternals.ReactCurrentDispatcher;
var prefix;
function describeBuiltInComponentFrame(name, source, ownerFn) {
  {
    if (prefix === undefined) {
      // Extract the VM specific prefix used by each line.
      try {
        throw Error();
      } catch (x) {
        var match = x.stack.trim().match(/\n( *(at )?)/);
        prefix = match && match[1] || '';
      }
    } // We use the prefix to ensure our stacks line up with native stack frames.


    return '\n' + prefix + name;
  }
}
var reentry = false;
var componentFrameCache;

{
  var PossiblyWeakMap = typeof WeakMap === 'function' ? WeakMap : Map;
  componentFrameCache = new PossiblyWeakMap();
}

function describeNativeComponentFrame(fn, construct) {
  // If something asked for a stack inside a fake render, it should get ignored.
  if ( !fn || reentry) {
    return '';
  }

  {
    var frame = componentFrameCache.get(fn);

    if (frame !== undefined) {
      return frame;
    }
  }

  var control;
  reentry = true;
  var previousPrepareStackTrace = Error.prepareStackTrace; // $FlowFixMe It does accept undefined.

  Error.prepareStackTrace = undefined;
  var previousDispatcher;

  {
    previousDispatcher = ReactCurrentDispatcher.current; // Set the dispatcher in DEV because this might be call in the render function
    // for warnings.

    ReactCurrentDispatcher.current = null;
    disableLogs();
  }

  try {
    // This should throw.
    if (construct) {
      // Something should be setting the props in the constructor.
      var Fake = function () {
        throw Error();
      }; // $FlowFixMe


      Object.defineProperty(Fake.prototype, 'props', {
        set: function () {
          // We use a throwing setter instead of frozen or non-writable props
          // because that won't throw in a non-strict mode function.
          throw Error();
        }
      });

      if (typeof Reflect === 'object' && Reflect.construct) {
        // We construct a different control for this case to include any extra
        // frames added by the construct call.
        try {
          Reflect.construct(Fake, []);
        } catch (x) {
          control = x;
        }

        Reflect.construct(fn, [], Fake);
      } else {
        try {
          Fake.call();
        } catch (x) {
          control = x;
        }

        fn.call(Fake.prototype);
      }
    } else {
      try {
        throw Error();
      } catch (x) {
        control = x;
      }

      fn();
    }
  } catch (sample) {
    // This is inlined manually because closure doesn't do it for us.
    if (sample && control && typeof sample.stack === 'string') {
      // This extracts the first frame from the sample that isn't also in the control.
      // Skipping one frame that we assume is the frame that calls the two.
      var sampleLines = sample.stack.split('\n');
      var controlLines = control.stack.split('\n');
      var s = sampleLines.length - 1;
      var c = controlLines.length - 1;

      while (s >= 1 && c >= 0 && sampleLines[s] !== controlLines[c]) {
        // We expect at least one stack frame to be shared.
        // Typically this will be the root most one. However, stack frames may be
        // cut off due to maximum stack limits. In this case, one maybe cut off
        // earlier than the other. We assume that the sample is longer or the same
        // and there for cut off earlier. So we should find the root most frame in
        // the sample somewhere in the control.
        c--;
      }

      for (; s >= 1 && c >= 0; s--, c--) {
        // Next we find the first one that isn't the same which should be the
        // frame that called our sample function and the control.
        if (sampleLines[s] !== controlLines[c]) {
          // In V8, the first line is describing the message but other VMs don't.
          // If we're about to return the first line, and the control is also on the same
          // line, that's a pretty good indicator that our sample threw at same line as
          // the control. I.e. before we entered the sample frame. So we ignore this result.
          // This can happen if you passed a class to function component, or non-function.
          if (s !== 1 || c !== 1) {
            do {
              s--;
              c--; // We may still have similar intermediate frames from the construct call.
              // The next one that isn't the same should be our match though.

              if (c < 0 || sampleLines[s] !== controlLines[c]) {
                // V8 adds a "new" prefix for native classes. Let's remove it to make it prettier.
                var _frame = '\n' + sampleLines[s].replace(' at new ', ' at '); // If our component frame is labeled "<anonymous>"
                // but we have a user-provided "displayName"
                // splice it in to make the stack more readable.


                if (fn.displayName && _frame.includes('<anonymous>')) {
                  _frame = _frame.replace('<anonymous>', fn.displayName);
                }

                {
                  if (typeof fn === 'function') {
                    componentFrameCache.set(fn, _frame);
                  }
                } // Return the line we found.


                return _frame;
              }
            } while (s >= 1 && c >= 0);
          }

          break;
        }
      }
    }
  } finally {
    reentry = false;

    {
      ReactCurrentDispatcher.current = previousDispatcher;
      reenableLogs();
    }

    Error.prepareStackTrace = previousPrepareStackTrace;
  } // Fallback to just using the name if we couldn't make it throw.


  var name = fn ? fn.displayName || fn.name : '';
  var syntheticFrame = name ? describeBuiltInComponentFrame(name) : '';

  {
    if (typeof fn === 'function') {
      componentFrameCache.set(fn, syntheticFrame);
    }
  }

  return syntheticFrame;
}
function describeFunctionComponentFrame(fn, source, ownerFn) {
  {
    return describeNativeComponentFrame(fn, false);
  }
}

function shouldConstruct(Component) {
  var prototype = Component.prototype;
  return !!(prototype && prototype.isReactComponent);
}

function describeUnknownElementTypeFrameInDEV(type, source, ownerFn) {

  if (type == null) {
    return '';
  }

  if (typeof type === 'function') {
    {
      return describeNativeComponentFrame(type, shouldConstruct(type));
    }
  }

  if (typeof type === 'string') {
    return describeBuiltInComponentFrame(type);
  }

  switch (type) {
    case REACT_SUSPENSE_TYPE:
      return describeBuiltInComponentFrame('Suspense');

    case REACT_SUSPENSE_LIST_TYPE:
      return describeBuiltInComponentFrame('SuspenseList');
  }

  if (typeof type === 'object') {
    switch (type.$$typeof) {
      case REACT_FORWARD_REF_TYPE:
        return describeFunctionComponentFrame(type.render);

      case REACT_MEMO_TYPE:
        // Memo may contain any component type so we recursively resolve it.
        return describeUnknownElementTypeFrameInDEV(type.type, source, ownerFn);

      case REACT_LAZY_TYPE:
        {
          var lazyComponent = type;
          var payload = lazyComponent._payload;
          var init = lazyComponent._init;

          try {
            // Lazy may contain any component type so we recursively resolve it.
            return describeUnknownElementTypeFrameInDEV(init(payload), source, ownerFn);
          } catch (x) {}
        }
    }
  }

  return '';
}

var hasOwnProperty = Object.prototype.hasOwnProperty;

var loggedTypeFailures = {};
var ReactDebugCurrentFrame = ReactSharedInternals.ReactDebugCurrentFrame;

function setCurrentlyValidatingElement(element) {
  {
    if (element) {
      var owner = element._owner;
      var stack = describeUnknownElementTypeFrameInDEV(element.type, element._source, owner ? owner.type : null);
      ReactDebugCurrentFrame.setExtraStackFrame(stack);
    } else {
      ReactDebugCurrentFrame.setExtraStackFrame(null);
    }
  }
}

function checkPropTypes(typeSpecs, values, location, componentName, element) {
  {
    // $FlowFixMe This is okay but Flow doesn't know it.
    var has = Function.call.bind(hasOwnProperty);

    for (var typeSpecName in typeSpecs) {
      if (has(typeSpecs, typeSpecName)) {
        var error$1 = void 0; // Prop type validation may throw. In case they do, we don't want to
        // fail the render phase where it didn't fail before. So we log it.
        // After these have been cleaned up, we'll let them throw.

        try {
          // This is intentionally an invariant that gets caught. It's the same
          // behavior as without this statement except with a better message.
          if (typeof typeSpecs[typeSpecName] !== 'function') {
            // eslint-disable-next-line react-internal/prod-error-codes
            var err = Error((componentName || 'React class') + ': ' + location + ' type `' + typeSpecName + '` is invalid; ' + 'it must be a function, usually from the `prop-types` package, but received `' + typeof typeSpecs[typeSpecName] + '`.' + 'This often happens because of typos such as `PropTypes.function` instead of `PropTypes.func`.');
            err.name = 'Invariant Violation';
            throw err;
          }

          error$1 = typeSpecs[typeSpecName](values, typeSpecName, componentName, location, null, 'SECRET_DO_NOT_PASS_THIS_OR_YOU_WILL_BE_FIRED');
        } catch (ex) {
          error$1 = ex;
        }

        if (error$1 && !(error$1 instanceof Error)) {
          setCurrentlyValidatingElement(element);

          error('%s: type specification of %s' + ' `%s` is invalid; the type checker ' + 'function must return `null` or an `Error` but returned a %s. ' + 'You may have forgotten to pass an argument to the type checker ' + 'creator (arrayOf, instanceOf, objectOf, oneOf, oneOfType, and ' + 'shape all require an argument).', componentName || 'React class', location, typeSpecName, typeof error$1);

          setCurrentlyValidatingElement(null);
        }

        if (error$1 instanceof Error && !(error$1.message in loggedTypeFailures)) {
          // Only monitor this failure once because there tends to be a lot of the
          // same error.
          loggedTypeFailures[error$1.message] = true;
          setCurrentlyValidatingElement(element);

          error('Failed %s type: %s', location, error$1.message);

          setCurrentlyValidatingElement(null);
        }
      }
    }
  }
}

var isArrayImpl = Array.isArray; // eslint-disable-next-line no-redeclare

function isArray(a) {
  return isArrayImpl(a);
}

/*
 * The `'' + value` pattern (used in in perf-sensitive code) throws for Symbol
 * and Temporal.* types. See https://github.com/facebook/react/pull/22064.
 *
 * The functions in this module will throw an easier-to-understand,
 * easier-to-debug exception with a clear errors message message explaining the
 * problem. (Instead of a confusing exception thrown inside the implementation
 * of the `value` object).
 */
// $FlowFixMe only called in DEV, so void return is not possible.
function typeName(value) {
  {
    // toStringTag is needed for namespaced types like Temporal.Instant
    var hasToStringTag = typeof Symbol === 'function' && Symbol.toStringTag;
    var type = hasToStringTag && value[Symbol.toStringTag] || value.constructor.name || 'Object';
    return type;
  }
} // $FlowFixMe only called in DEV, so void return is not possible.


function willCoercionThrow(value) {
  {
    try {
      testStringCoercion(value);
      return false;
    } catch (e) {
      return true;
    }
  }
}

function testStringCoercion(value) {
  // If you ended up here by following an exception call stack, here's what's
  // happened: you supplied an object or symbol value to React (as a prop, key,
  // DOM attribute, CSS property, string ref, etc.) and when React tried to
  // coerce it to a string using `'' + value`, an exception was thrown.
  //
  // The most common types that will cause this exception are `Symbol` instances
  // and Temporal objects like `Temporal.Instant`. But any object that has a
  // `valueOf` or `[Symbol.toPrimitive]` method that throws will also cause this
  // exception. (Library authors do this to prevent users from using built-in
  // numeric operators like `+` or comparison operators like `>=` because custom
  // methods are needed to perform accurate arithmetic or comparison.)
  //
  // To fix the problem, coerce this object or symbol value to a string before
  // passing it to React. The most reliable way is usually `String(value)`.
  //
  // To find which value is throwing, check the browser or debugger console.
  // Before this exception was thrown, there should be `console.error` output
  // that shows the type (Symbol, Temporal.PlainDate, etc.) that caused the
  // problem and how that type was used: key, atrribute, input value prop, etc.
  // In most cases, this console output also shows the component and its
  // ancestor components where the exception happened.
  //
  // eslint-disable-next-line react-internal/safe-string-coercion
  return '' + value;
}
function checkKeyStringCoercion(value) {
  {
    if (willCoercionThrow(value)) {
      error('The provided key is an unsupported type %s.' + ' This value must be coerced to a string before before using it here.', typeName(value));

      return testStringCoercion(value); // throw (to help callers find troubleshooting comments)
    }
  }
}

var ReactCurrentOwner = ReactSharedInternals.ReactCurrentOwner;
var RESERVED_PROPS = {
  key: true,
  ref: true,
  __self: true,
  __source: true
};
var specialPropKeyWarningShown;
var specialPropRefWarningShown;
var didWarnAboutStringRefs;

{
  didWarnAboutStringRefs = {};
}

function hasValidRef(config) {
  {
    if (hasOwnProperty.call(config, 'ref')) {
      var getter = Object.getOwnPropertyDescriptor(config, 'ref').get;

      if (getter && getter.isReactWarning) {
        return false;
      }
    }
  }

  return config.ref !== undefined;
}

function hasValidKey(config) {
  {
    if (hasOwnProperty.call(config, 'key')) {
      var getter = Object.getOwnPropertyDescriptor(config, 'key').get;

      if (getter && getter.isReactWarning) {
        return false;
      }
    }
  }

  return config.key !== undefined;
}

function warnIfStringRefCannotBeAutoConverted(config, self) {
  {
    if (typeof config.ref === 'string' && ReactCurrentOwner.current && self && ReactCurrentOwner.current.stateNode !== self) {
      var componentName = getComponentNameFromType(ReactCurrentOwner.current.type);

      if (!didWarnAboutStringRefs[componentName]) {
        error('Component "%s" contains the string ref "%s". ' + 'Support for string refs will be removed in a future major release. ' + 'This case cannot be automatically converted to an arrow function. ' + 'We ask you to manually fix this case by using useRef() or createRef() instead. ' + 'Learn more about using refs safely here: ' + 'https://reactjs.org/link/strict-mode-string-ref', getComponentNameFromType(ReactCurrentOwner.current.type), config.ref);

        didWarnAboutStringRefs[componentName] = true;
      }
    }
  }
}

function defineKeyPropWarningGetter(props, displayName) {
  {
    var warnAboutAccessingKey = function () {
      if (!specialPropKeyWarningShown) {
        specialPropKeyWarningShown = true;

        error('%s: `key` is not a prop. Trying to access it will result ' + 'in `undefined` being returned. If you need to access the same ' + 'value within the child component, you should pass it as a different ' + 'prop. (https://reactjs.org/link/special-props)', displayName);
      }
    };

    warnAboutAccessingKey.isReactWarning = true;
    Object.defineProperty(props, 'key', {
      get: warnAboutAccessingKey,
      configurable: true
    });
  }
}

function defineRefPropWarningGetter(props, displayName) {
  {
    var warnAboutAccessingRef = function () {
      if (!specialPropRefWarningShown) {
        specialPropRefWarningShown = true;

        error('%s: `ref` is not a prop. Trying to access it will result ' + 'in `undefined` being returned. If you need to access the same ' + 'value within the child component, you should pass it as a different ' + 'prop. (https://reactjs.org/link/special-props)', displayName);
      }
    };

    warnAboutAccessingRef.isReactWarning = true;
    Object.defineProperty(props, 'ref', {
      get: warnAboutAccessingRef,
      configurable: true
    });
  }
}
/**
 * Factory method to create a new React element. This no longer adheres to
 * the class pattern, so do not use new to call it. Also, instanceof check
 * will not work. Instead test $$typeof field against Symbol.for('react.element') to check
 * if something is a React Element.
 *
 * @param {*} type
 * @param {*} props
 * @param {*} key
 * @param {string|object} ref
 * @param {*} owner
 * @param {*} self A *temporary* helper to detect places where `this` is
 * different from the `owner` when React.createElement is called, so that we
 * can warn. We want to get rid of owner and replace string `ref`s with arrow
 * functions, and as long as `this` and owner are the same, there will be no
 * change in behavior.
 * @param {*} source An annotation object (added by a transpiler or otherwise)
 * indicating filename, line number, and/or other information.
 * @internal
 */


var ReactElement = function (type, key, ref, self, source, owner, props) {
  var element = {
    // This tag allows us to uniquely identify this as a React Element
    $$typeof: REACT_ELEMENT_TYPE,
    // Built-in properties that belong on the element
    type: type,
    key: key,
    ref: ref,
    props: props,
    // Record the component responsible for creating this element.
    _owner: owner
  };

  {
    // The validation flag is currently mutative. We put it on
    // an external backing store so that we can freeze the whole object.
    // This can be replaced with a WeakMap once they are implemented in
    // commonly used development environments.
    element._store = {}; // To make comparing ReactElements easier for testing purposes, we make
    // the validation flag non-enumerable (where possible, which should
    // include every environment we run tests in), so the test framework
    // ignores it.

    Object.defineProperty(element._store, 'validated', {
      configurable: false,
      enumerable: false,
      writable: true,
      value: false
    }); // self and source are DEV only properties.

    Object.defineProperty(element, '_self', {
      configurable: false,
      enumerable: false,
      writable: false,
      value: self
    }); // Two elements created in two different places should be considered
    // equal for testing purposes and therefore we hide it from enumeration.

    Object.defineProperty(element, '_source', {
      configurable: false,
      enumerable: false,
      writable: false,
      value: source
    });

    if (Object.freeze) {
      Object.freeze(element.props);
      Object.freeze(element);
    }
  }

  return element;
};
/**
 * https://github.com/reactjs/rfcs/pull/107
 * @param {*} type
 * @param {object} props
 * @param {string} key
 */

function jsxDEV(type, config, maybeKey, source, self) {
  {
    var propName; // Reserved names are extracted

    var props = {};
    var key = null;
    var ref = null; // Currently, key can be spread in as a prop. This causes a potential
    // issue if key is also explicitly declared (ie. <div {...props} key="Hi" />
    // or <div key="Hi" {...props} /> ). We want to deprecate key spread,
    // but as an intermediary step, we will use jsxDEV for everything except
    // <div {...props} key="Hi" />, because we aren't currently able to tell if
    // key is explicitly declared to be undefined or not.

    if (maybeKey !== undefined) {
      {
        checkKeyStringCoercion(maybeKey);
      }

      key = '' + maybeKey;
    }

    if (hasValidKey(config)) {
      {
        checkKeyStringCoercion(config.key);
      }

      key = '' + config.key;
    }

    if (hasValidRef(config)) {
      ref = config.ref;
      warnIfStringRefCannotBeAutoConverted(config, self);
    } // Remaining properties are added to a new props object


    for (propName in config) {
      if (hasOwnProperty.call(config, propName) && !RESERVED_PROPS.hasOwnProperty(propName)) {
        props[propName] = config[propName];
      }
    } // Resolve default props


    if (type && type.defaultProps) {
      var defaultProps = type.defaultProps;

      for (propName in defaultProps) {
        if (props[propName] === undefined) {
          props[propName] = defaultProps[propName];
        }
      }
    }

    if (key || ref) {
      var displayName = typeof type === 'function' ? type.displayName || type.name || 'Unknown' : type;

      if (key) {
        defineKeyPropWarningGetter(props, displayName);
      }

      if (ref) {
        defineRefPropWarningGetter(props, displayName);
      }
    }

    return ReactElement(type, key, ref, self, source, ReactCurrentOwner.current, props);
  }
}

var ReactCurrentOwner$1 = ReactSharedInternals.ReactCurrentOwner;
var ReactDebugCurrentFrame$1 = ReactSharedInternals.ReactDebugCurrentFrame;

function setCurrentlyValidatingElement$1(element) {
  {
    if (element) {
      var owner = element._owner;
      var stack = describeUnknownElementTypeFrameInDEV(element.type, element._source, owner ? owner.type : null);
      ReactDebugCurrentFrame$1.setExtraStackFrame(stack);
    } else {
      ReactDebugCurrentFrame$1.setExtraStackFrame(null);
    }
  }
}

var propTypesMisspellWarningShown;

{
  propTypesMisspellWarningShown = false;
}
/**
 * Verifies the object is a ReactElement.
 * See https://reactjs.org/docs/react-api.html#isvalidelement
 * @param {?object} object
 * @return {boolean} True if `object` is a ReactElement.
 * @final
 */


function isValidElement(object) {
  {
    return typeof object === 'object' && object !== null && object.$$typeof === REACT_ELEMENT_TYPE;
  }
}

function getDeclarationErrorAddendum() {
  {
    if (ReactCurrentOwner$1.current) {
      var name = getComponentNameFromType(ReactCurrentOwner$1.current.type);

      if (name) {
        return '\n\nCheck the render method of `' + name + '`.';
      }
    }

    return '';
  }
}

function getSourceInfoErrorAddendum(source) {
  {
    if (source !== undefined) {
      var fileName = source.fileName.replace(/^.*[\\\/]/, '');
      var lineNumber = source.lineNumber;
      return '\n\nCheck your code at ' + fileName + ':' + lineNumber + '.';
    }

    return '';
  }
}
/**
 * Warn if there's no key explicitly set on dynamic arrays of children or
 * object keys are not valid. This allows us to keep track of children between
 * updates.
 */


var ownerHasKeyUseWarning = {};

function getCurrentComponentErrorInfo(parentType) {
  {
    var info = getDeclarationErrorAddendum();

    if (!info) {
      var parentName = typeof parentType === 'string' ? parentType : parentType.displayName || parentType.name;

      if (parentName) {
        info = "\n\nCheck the top-level render call using <" + parentName + ">.";
      }
    }

    return info;
  }
}
/**
 * Warn if the element doesn't have an explicit key assigned to it.
 * This element is in an array. The array could grow and shrink or be
 * reordered. All children that haven't already been validated are required to
 * have a "key" property assigned to it. Error statuses are cached so a warning
 * will only be shown once.
 *
 * @internal
 * @param {ReactElement} element Element that requires a key.
 * @param {*} parentType element's parent's type.
 */


function validateExplicitKey(element, parentType) {
  {
    if (!element._store || element._store.validated || element.key != null) {
      return;
    }

    element._store.validated = true;
    var currentComponentErrorInfo = getCurrentComponentErrorInfo(parentType);

    if (ownerHasKeyUseWarning[currentComponentErrorInfo]) {
      return;
    }

    ownerHasKeyUseWarning[currentComponentErrorInfo] = true; // Usually the current owner is the offender, but if it accepts children as a
    // property, it may be the creator of the child that's responsible for
    // assigning it a key.

    var childOwner = '';

    if (element && element._owner && element._owner !== ReactCurrentOwner$1.current) {
      // Give the component that originally created this child.
      childOwner = " It was passed a child from " + getComponentNameFromType(element._owner.type) + ".";
    }

    setCurrentlyValidatingElement$1(element);

    error('Each child in a list should have a unique "key" prop.' + '%s%s See https://reactjs.org/link/warning-keys for more information.', currentComponentErrorInfo, childOwner);

    setCurrentlyValidatingElement$1(null);
  }
}
/**
 * Ensure that every element either is passed in a static location, in an
 * array with an explicit keys property defined, or in an object literal
 * with valid key property.
 *
 * @internal
 * @param {ReactNode} node Statically passed child of any type.
 * @param {*} parentType node's parent's type.
 */


function validateChildKeys(node, parentType) {
  {
    if (typeof node !== 'object') {
      return;
    }

    if (isArray(node)) {
      for (var i = 0; i < node.length; i++) {
        var child = node[i];

        if (isValidElement(child)) {
          validateExplicitKey(child, parentType);
        }
      }
    } else if (isValidElement(node)) {
      // This element was passed in a valid location.
      if (node._store) {
        node._store.validated = true;
      }
    } else if (node) {
      var iteratorFn = getIteratorFn(node);

      if (typeof iteratorFn === 'function') {
        // Entry iterators used to provide implicit keys,
        // but now we print a separate warning for them later.
        if (iteratorFn !== node.entries) {
          var iterator = iteratorFn.call(node);
          var step;

          while (!(step = iterator.next()).done) {
            if (isValidElement(step.value)) {
              validateExplicitKey(step.value, parentType);
            }
          }
        }
      }
    }
  }
}
/**
 * Given an element, validate that its props follow the propTypes definition,
 * provided by the type.
 *
 * @param {ReactElement} element
 */


function validatePropTypes(element) {
  {
    var type = element.type;

    if (type === null || type === undefined || typeof type === 'string') {
      return;
    }

    var propTypes;

    if (typeof type === 'function') {
      propTypes = type.propTypes;
    } else if (typeof type === 'object' && (type.$$typeof === REACT_FORWARD_REF_TYPE || // Note: Memo only checks outer props here.
    // Inner props are checked in the reconciler.
    type.$$typeof === REACT_MEMO_TYPE)) {
      propTypes = type.propTypes;
    } else {
      return;
    }

    if (propTypes) {
      // Intentionally inside to avoid triggering lazy initializers:
      var name = getComponentNameFromType(type);
      checkPropTypes(propTypes, element.props, 'prop', name, element);
    } else if (type.PropTypes !== undefined && !propTypesMisspellWarningShown) {
      propTypesMisspellWarningShown = true; // Intentionally inside to avoid triggering lazy initializers:

      var _name = getComponentNameFromType(type);

      error('Component %s declared `PropTypes` instead of `propTypes`. Did you misspell the property assignment?', _name || 'Unknown');
    }

    if (typeof type.getDefaultProps === 'function' && !type.getDefaultProps.isReactClassApproved) {
      error('getDefaultProps is only used on classic React.createClass ' + 'definitions. Use a static property named `defaultProps` instead.');
    }
  }
}
/**
 * Given a fragment, validate that it can only be provided with fragment props
 * @param {ReactElement} fragment
 */


function validateFragmentProps(fragment) {
  {
    var keys = Object.keys(fragment.props);

    for (var i = 0; i < keys.length; i++) {
      var key = keys[i];

      if (key !== 'children' && key !== 'key') {
        setCurrentlyValidatingElement$1(fragment);

        error('Invalid prop `%s` supplied to `React.Fragment`. ' + 'React.Fragment can only have `key` and `children` props.', key);

        setCurrentlyValidatingElement$1(null);
        break;
      }
    }

    if (fragment.ref !== null) {
      setCurrentlyValidatingElement$1(fragment);

      error('Invalid attribute `ref` supplied to `React.Fragment`.');

      setCurrentlyValidatingElement$1(null);
    }
  }
}

var didWarnAboutKeySpread = {};
function jsxWithValidation(type, props, key, isStaticChildren, source, self) {
  {
    var validType = isValidElementType(type); // We warn in this case but don't throw. We expect the element creation to
    // succeed and there will likely be errors in render.

    if (!validType) {
      var info = '';

      if (type === undefined || typeof type === 'object' && type !== null && Object.keys(type).length === 0) {
        info += ' You likely forgot to export your component from the file ' + "it's defined in, or you might have mixed up default and named imports.";
      }

      var sourceInfo = getSourceInfoErrorAddendum(source);

      if (sourceInfo) {
        info += sourceInfo;
      } else {
        info += getDeclarationErrorAddendum();
      }

      var typeString;

      if (type === null) {
        typeString = 'null';
      } else if (isArray(type)) {
        typeString = 'array';
      } else if (type !== undefined && type.$$typeof === REACT_ELEMENT_TYPE) {
        typeString = "<" + (getComponentNameFromType(type.type) || 'Unknown') + " />";
        info = ' Did you accidentally export a JSX literal instead of a component?';
      } else {
        typeString = typeof type;
      }

      error('React.jsx: type is invalid -- expected a string (for ' + 'built-in components) or a class/function (for composite ' + 'components) but got: %s.%s', typeString, info);
    }

    var element = jsxDEV(type, props, key, source, self); // The result can be nullish if a mock or a custom function is used.
    // TODO: Drop this when these are no longer allowed as the type argument.

    if (element == null) {
      return element;
    } // Skip key warning if the type isn't valid since our key validation logic
    // doesn't expect a non-string/function type and can throw confusing errors.
    // We don't want exception behavior to differ between dev and prod.
    // (Rendering will throw with a helpful message and as soon as the type is
    // fixed, the key warnings will appear.)


    if (validType) {
      var children = props.children;

      if (children !== undefined) {
        if (isStaticChildren) {
          if (isArray(children)) {
            for (var i = 0; i < children.length; i++) {
              validateChildKeys(children[i], type);
            }

            if (Object.freeze) {
              Object.freeze(children);
            }
          } else {
            error('React.jsx: Static children should always be an array. ' + 'You are likely explicitly calling React.jsxs or React.jsxDEV. ' + 'Use the Babel transform instead.');
          }
        } else {
          validateChildKeys(children, type);
        }
      }
    }

    {
      if (hasOwnProperty.call(props, 'key')) {
        var componentName = getComponentNameFromType(type);
        var keys = Object.keys(props).filter(function (k) {
          return k !== 'key';
        });
        var beforeExample = keys.length > 0 ? '{key: someKey, ' + keys.join(': ..., ') + ': ...}' : '{key: someKey}';

        if (!didWarnAboutKeySpread[componentName + beforeExample]) {
          var afterExample = keys.length > 0 ? '{' + keys.join(': ..., ') + ': ...}' : '{}';

          error('A props object containing a "key" prop is being spread into JSX:\n' + '  let props = %s;\n' + '  <%s {...props} />\n' + 'React keys must be passed directly to JSX without using spread:\n' + '  let props = %s;\n' + '  <%s key={someKey} {...props} />', beforeExample, componentName, afterExample, componentName);

          didWarnAboutKeySpread[componentName + beforeExample] = true;
        }
      }
    }

    if (type === REACT_FRAGMENT_TYPE) {
      validateFragmentProps(element);
    } else {
      validatePropTypes(element);
    }

    return element;
  }
} // These two functions exist to still get child warnings in dev
// even with the prod transform. This means that jsxDEV is purely
// opt-in behavior for better messages but that we won't stop
// giving you warnings if you use production apis.

function jsxWithValidationStatic(type, props, key) {
  {
    return jsxWithValidation(type, props, key, true);
  }
}
function jsxWithValidationDynamic(type, props, key) {
  {
    return jsxWithValidation(type, props, key, false);
  }
}

var jsx =  jsxWithValidationDynamic ; // we may want to special case jsxs internally to take advantage of static children.
// for now we can ship identical prod functions

var jsxs =  jsxWithValidationStatic ;

exports.Fragment = REACT_FRAGMENT_TYPE;
exports.jsx = jsx;
exports.jsxs = jsxs;
  })();
}


/***/ }),

/***/ "./node_modules/react/jsx-runtime.js":
/*!*******************************************!*\
  !*** ./node_modules/react/jsx-runtime.js ***!
  \*******************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



if (false) // removed by dead control flow
{} else {
  module.exports = __webpack_require__(/*! ./cjs/react-jsx-runtime.development.js */ "./node_modules/react/cjs/react-jsx-runtime.development.js");
}


/***/ }),

/***/ "./src/js/paidy/main-hooks/form-info.jsx":
/*!***********************************************!*\
  !*** ./src/js/paidy/main-hooks/form-info.jsx ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   AgreementInfo: () => (/* binding */ AgreementInfo),
/* harmony export */   ApplyCompletedMessage: () => (/* binding */ ApplyCompletedMessage),
/* harmony export */   FlowExplanation: () => (/* binding */ FlowExplanation),
/* harmony export */   ReviewApprovedMessage: () => (/* binding */ ReviewApprovedMessage),
/* harmony export */   ReviewRejectedMessage: () => (/* binding */ ReviewRejectedMessage),
/* harmony export */   SettingCompletedMessage: () => (/* binding */ SettingCompletedMessage),
/* harmony export */   SettingSandboxMessage: () => (/* binding */ SettingSandboxMessage)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_notices__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/notices */ "@wordpress/notices");
/* harmony import */ var _wordpress_notices__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_notices__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! react/jsx-runtime */ "./node_modules/react/jsx-runtime.js");







const FlowExplanation = () => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.Fragment, {
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.__experimentalHeading, {
      level: 3,
      children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Application flow', 'woocommerce-for-japan')
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("ol", {
      className: "paidy-flow",
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("li", {
        children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Please fill in all the fields on this application screen and click the "Agree to the above and apply" button at the bottom of the screen.', 'woocommerce-for-japan')
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("li", {
        children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Paidy Co., Ltd. will review your application information and may take up to 5 business days.', 'woocommerce-for-japan')
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("li", {
        children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('The results will be notified via email and dashboard.', 'woocommerce-for-japan')
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("li", {
        children: [(0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('If your application is approved, you will be able to activate Paidy from your dashboard.', 'woocommerce-for-japan'), " ", /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("br", {}), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('If your application is rejected, you will not be able to use Paidy.', 'woocommerce-for-japan')]
      })]
    })]
  });
};
const AgreementInfo = () => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.Fragment, {
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("div", {
      className: "paidy-heading",
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.__experimentalHeading, {
        level: 3,
        children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Agreement', 'woocommerce-for-japan')
      })
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("div", {
      className: "paidy-agreement",
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("p", {
        className: "paidy-agreement-text",
        children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Please confirm and agree to the following contents.', 'woocommerce-for-japan')
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("ol", {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("li", {
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("a", {
            href: "https://terms.paidy.com/",
            target: "_blank",
            children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Paidy Merchant Terms and Conditions', 'woocommerce-for-japan')
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("li", {
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Agreement on handling of affiliated store information', 'woocommerce-for-japan')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("li", {
          children: [(0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Add the notation specified by our company to the notation based on the Specified Commercial Transactions Act.', 'woocommerce-for-japan'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("br", {}), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("a", {
            href: "https://merchant-support.paidy.com/hc/ja/articles/16629903258649-%E7%89%B9%E5%95%86%E6%B3%95%E3%81%AB%E5%9F%BA%E3%81%A5%E3%81%8F%E8%A1%A8%E7%A4%BA%E3%82%B5%E3%83%B3%E3%83%97%E3%83%AB",
            target: "_blank",
            children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Display sample based on the Specified Commercial Transactions Act', 'woocommerce-for-japan')
          })]
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("li", {
          children: [(0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Add the specified notation to the privacy policy page.', 'woocommerce-for-japan'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("br", {}), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("a", {
            href: "https://merchant-support.paidy.com/hc/ja/articles/16631714849561-%E3%83%97%E3%83%A9%E3%82%A4%E3%83%90%E3%82%B7%E3%83%BC%E3%83%9D%E3%83%AA%E3%82%B7%E3%83%BC%E3%81%AE%E8%A8%98%E5%85%A5%E4%BE%8B",
            target: "_blank",
            children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Example of a privacy policy', 'woocommerce-for-japan')
          })]
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("li", {
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Paidy Inc. will set up the API key on your e-commerce site via WooCommerce on your behalf.', 'woocommerce-for-japan')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("li", {
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Our company (Shokunin Kobo LLC) will provide personal information to third parties when disclosing such information for purposes such as member store screening by payment companies.', 'woocommerce-for-japan')
        })]
      })]
    })]
  });
};
const ApplyCompletedMessage = () => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("div", {
    className: "paidy-completed-message",
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.__experimentalHeading, {
      level: 3,
      children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Status: Under review', 'woocommerce-for-japan')
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("p", {
      children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Thank you for your application. We will notify you of the results via email and dashboard.', 'woocommerce-for-japan')
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("ul", {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("li", {
        children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('The review process may take up to 5 business days.', 'woocommerce-for-japan')
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("li", {
        children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Inquiries regarding screening: sales@paidy.com', 'woocommerce-for-japan')
      })]
    })]
  });
};
const ReviewRejectedMessage = () => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("div", {
    className: "paidy-rejected-message",
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.__experimentalHeading, {
      level: 3,
      children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Status: The review was rejected', 'woocommerce-for-japan')
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("ul", {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("li", {
        children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('After careful and repeated review, we regret to inform you that we have decided to postpone the transaction at this time.', 'woocommerce-for-japan')
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("li", {
        children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('We sincerely apologize for not being able to meet your expectations.', 'woocommerce-for-japan')
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("li", {
        children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Please understand that we cannot provide details about the content of the review.', 'woocommerce-for-japan')
      })]
    })]
  });
};
const EnableTestButton = ({
  onClick
}) => {
  const [environment, setEnvironment] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)();
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useEffect)(() => {
    // WooCommerce Payment Gateway APIを使用
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_3___default()({
      path: '/wc/v3/payment_gateways/paidy'
    }).then(response => {
      const currentEnvironment = response.settings?.environment?.value || response.settings?.environment || '';
      setEnvironment(currentEnvironment);
    }).catch(error => {
      return _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_3___default()({
        path: '/wp/v2/settings'
      });
    }).then(settings => {
      if (settings) {
        const paidySettings = settings.woocommerce_paidy_settings || {};
        if (!environment) {
          // まだ環境が設定されていない場合のみ
          setEnvironment(paidySettings.environment || '');
        }
      }
    }).catch(error => {
      console.error('Settings API Error:', error);
    });
  }, []);
  if (environment === 'sandbox') {
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("div", {
      className: "paidy-enabled-test-message",
      children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Now test mode', 'woocommerce-for-japan')
    });
  } else {
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Button, {
      className: "paidy-button test-button",
      onClick: onClick,
      children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Enable test mode', 'woocommerce-for-japan')
    });
  }
};
const ReviewApprovedMessage = () => {
  const [isLoading, setIsLoading] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)(false);
  const [environment, setEnvironment] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)();
  const {
    createErrorNotice,
    createSuccessNotice
  } = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_5__.useDispatch)(_wordpress_notices__WEBPACK_IMPORTED_MODULE_4__.store);
  const restUrl = window.paidyForWcSettings?.restUrl || '';
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useEffect)(() => {
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_3___default()({
      path: '/wc/v3/payment_gateways/paidy'
    }).then(response => {
      const currentEnvironment = response.settings?.environment?.value || response.settings?.environment || '';
      console.log('Current Environment:', currentEnvironment);
      setEnvironment(currentEnvironment);

      // environmentがsandboxまたはliveの場合、CSSを変更
      if (currentEnvironment === 'sandbox' || currentEnvironment === 'live') {
        const paidySettingsElement = document.getElementById('paidy-payment-settings');
        if (paidySettingsElement) {
          paidySettingsElement.style.display = 'block';
          console.log('CSS updated: #paidy-payment-settings display set to block');
        } else {
          console.warn('Element #paidy-payment-settings not found');
        }
      }
    }).catch(error => {
      console.error('Failed to fetch environment:', error);
      // フォールバック: WordPress Settings API
      return _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_3___default()({
        path: '/wp/v2/settings'
      });
    }).then(settings => {
      if (settings && !environment) {
        const paidySettings = settings.woocommerce_paidy_settings || {};
        const fallbackEnvironment = paidySettings.environment || '';
        setEnvironment(fallbackEnvironment);

        // フォールバックでも同様にCSSを変更
        if (fallbackEnvironment === 'sandbox' || fallbackEnvironment === 'live') {
          const paidySettingsElement = document.getElementById('paidy-payment-settings');
          if (paidySettingsElement) {
            paidySettingsElement.style.display = 'block';
            console.log('CSS updated (fallback): #paidy-payment-settings display set to block');
          }
        }
      }
    }).catch(error => {
      console.error('Failed to fetch settings:', error);
    });
  }, []);
  const onSavingTestMode = () => {
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_3___default()({
      path: '/wc/v3/payment_gateways/paidy',
      method: 'PUT',
      data: {
        enabled: true,
        settings: {
          environment: 'sandbox'
        }
      }
    }).then(response => {
      createSuccessNotice((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Settings saved.', 'woocommerce-for-japan'));
      window.location.href = '/wp-admin/admin.php?page=wc-settings&tab=checkout&section=paidy';
    }).catch(error => {
      setIsLoading(false);
      createErrorNotice(error.message || (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Error enabling Paidy', 'woocommerce-for-japan'), {
        type: 'snackbar',
        isDismissible: true,
        autoDismiss: false
      });
    });
  };
  const onSavingProductionMode = () => {
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_3___default()({
      path: '/wc/v3/payment_gateways/paidy',
      method: 'PUT',
      data: {
        enabled: true,
        settings: {
          environment: 'live'
        }
      }
    }).then(response => {
      window.location.href = '/wp-admin/admin.php?page=wc-settings&tab=checkout&section=paidy';
    }).catch(error => {
      setIsLoading(false);
      createErrorNotice(error.message || (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Error enabling Paidy', 'woocommerce-for-japan'), {
        type: 'snackbar',
        isDismissible: true,
        autoDismiss: false
      });
    });
  };
  if (environment === 'sandbox') {
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.Fragment, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(SettingSandboxMessage, {}), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("div", {
        className: "paidy-enabled-button",
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Button, {
          className: "paidy-button production-button",
          onClick: onSavingProductionMode,
          disabled: isLoading,
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Enable production mode', 'woocommerce-for-japan')
        })
      })]
    });
  } else if (environment === 'live') {
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(SettingCompletedMessage, {});
  } else {
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("div", {
      className: "paidy-approved-message",
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.__experimentalHeading, {
        level: 3,
        children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Status: The review approved', 'woocommerce-for-japan')
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("p", {
        children: [(0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('The review has been completed and the merchant agreement has been concluded.', 'woocommerce-for-japan'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("br", {}), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('For terms of use, please check the terms and conditions notification email sent by Paidy Inc.', 'woocommerce-for-japan')]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("ul", {
        className: "paidy-approved-list",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("li", {
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('We recommend setting the Webhook URL in the Paidy merchant management screen before publishing in production mode. Set the Webhook URL to the following value.', 'woocommerce-for-japan')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("li", {
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('[Common for test and production]', 'woocommerce-for-japan')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("li", {
          children: [restUrl, "wp-json/paidy/v1/order/"]
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("li", {
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Please refer to the manual for information on the Paidy merchant management screen.', 'woocommerce-for-japan')
        })]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("div", {
        className: "paidy-enabled-button",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("p", {
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Please click one of the buttons below.', 'woocommerce-for-japan')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(EnableTestButton, {
          onClick: onSavingTestMode
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("br", {}), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("br", {}), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Button, {
          className: "paidy-button production-button",
          onClick: onSavingProductionMode,
          disabled: isLoading,
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Enable production mode', 'woocommerce-for-japan')
        })]
      })]
    });
  }
};
const SettingSandboxMessage = () => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("div", {
    className: "paidy-setting-sandbox-message",
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.__experimentalHeading, {
      level: 3,
      children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Paidy test payments enabled', 'woocommerce-for-japan')
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("p", {
      children: [(0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('You can now test Paidy payments.', 'woocommerce-for-japan'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("br", {}), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Please check the test payment flow.', 'woocommerce-for-japan'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("br", {}), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("a", {
        href: "https://paidy.com/docs/jp/testing.html",
        target: "_blank",
        children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Paidy test payment flow', 'woocommerce-for-japan')
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("br", {}), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('After confirming the test payment, please switch to production mode.', 'woocommerce-for-japan')]
    })]
  });
};
const SettingCompletedMessage = () => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("div", {
    className: "paidy-setting-completed-message",
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.__experimentalHeading, {
      level: 3,
      children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Paidy Payments is now live', 'woocommerce-for-japan')
    })
  });
};


/***/ }),

/***/ "./src/js/paidy/main-hooks/on-boarding-settings.js":
/*!*********************************************************!*\
  !*** ./src/js/paidy/main-hooks/on-boarding-settings.js ***!
  \*********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   useOnBoardingSettings: () => (/* binding */ useOnBoardingSettings)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_notices__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/notices */ "@wordpress/notices");
/* harmony import */ var _wordpress_notices__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_notices__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_4__);





const useOnBoardingSettings = () => {
  const [currentStep, setCurrentStep] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)(0);
  const [storeName, setStoreName] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)();
  const [siteName, setSiteName] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)();
  const [storeUrl, setStoreUrl] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)();
  const [registEmail, setRegistEmail] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)();
  const [contactPhone, setContactPhone] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)();
  const [representativeLastName, setRepesentativeLastName] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)();
  const [representativeFirstName, setRepresentativeFirstName] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)();
  const [representativeLastNameKana, setRepresentativeLastNameKana] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)();
  const [representativeFirstNameKana, setRepresentativeFirstNameKana] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)();
  const [representativeDateOfBirth, setRepresentativeDateOfBirth] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)();
  const [annualGrossValue, setAnnualGrossValue] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)();
  const [averagePurchaseAmount, setAveragePurchaseAmount] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)();
  const [securitySurvey01RadioControl, setSecuritySurvey01RadioControl] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)();
  const [securitySurvey01TextControl, setSecuritySurvey01TextControl] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)('');
  const [securitySurvey11CheckControl, setSecuritySurvey11CheckControl] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)();
  const [securitySurvey12CheckControl, setSecuritySurvey12CheckControl] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)();
  const [securitySurvey13CheckControl, setSecuritySurvey13CheckControl] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)();
  const [securitySurvey14CheckControl, setSecuritySurvey14CheckControl] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)();
  const [securitySurvey10TextAreaControl, setSecuritySurvey10TextAreaControl] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)('');
  const [securitySurvey08RadioControl, setSecuritySurvey08RadioControl] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)('no');
  const [securitySurvey09RadioControl, setSecuritySurvey09RadioControl] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)('no');
  const {
    createErrorNotice,
    createSuccessNotice
  } = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_4__.useDispatch)(_wordpress_notices__WEBPACK_IMPORTED_MODULE_2__.store);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useEffect)(() => {
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()({
      path: '/wp/v2/settings'
    }).then(settings => {
      const onBoardingSettings = settings.woocommerce_paidy_on_boarding_settings;
      setCurrentStep(Number(onBoardingSettings.currentStep || 0));
      setStoreName(onBoardingSettings.storeName || '');
      setSiteName(onBoardingSettings.siteName || '');
      setStoreUrl(onBoardingSettings.storeUrl || '');
      setRegistEmail(onBoardingSettings.registEmail || '');
      setContactPhone(onBoardingSettings.contactPhone || '');
      setRepesentativeLastName(onBoardingSettings.representativeLastName || '');
      setRepresentativeFirstName(onBoardingSettings.representativeFirstName || '');
      setRepresentativeLastNameKana(onBoardingSettings.representativeLastNameKana || '');
      setRepresentativeFirstNameKana(onBoardingSettings.representativeFirstNameKana || '');
      setRepresentativeDateOfBirth(onBoardingSettings.representativeDateOfBirth);
      setAnnualGrossValue(onBoardingSettings.annualGrossValue);
      setAveragePurchaseAmount(onBoardingSettings.averagePurchaseAmount);
      setSecuritySurvey01RadioControl(onBoardingSettings.securitySurvey01RadioControl || '');
      setSecuritySurvey01TextControl(onBoardingSettings.securitySurvey01TextControl || '');
      setSecuritySurvey11CheckControl(onBoardingSettings.securitySurvey11CheckControl);
      setSecuritySurvey12CheckControl(onBoardingSettings.securitySurvey12CheckControl);
      setSecuritySurvey13CheckControl(onBoardingSettings.securitySurvey13CheckControl);
      setSecuritySurvey14CheckControl(onBoardingSettings.securitySurvey14CheckControl);
      setSecuritySurvey10TextAreaControl(onBoardingSettings.securitySurvey10TextAreaControl || '');
      setSecuritySurvey08RadioControl(onBoardingSettings.securitySurvey08RadioControl || 'no');
      setSecuritySurvey09RadioControl(onBoardingSettings.securitySurvey09RadioControl || 'no');
    });
  }, []);
  const saveSettings = () => {
    let requiredFlag = false;
    const requiredFields = [{
      value: storeName,
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Trade name / Store name', 'woocommerce-for-japan')
    }, {
      value: siteName,
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Your EC site name', 'woocommerce-for-japan')
    }, {
      value: storeUrl,
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Store URL', 'woocommerce-for-japan')
    }, {
      value: registEmail,
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Email address for registering Paidy', 'woocommerce-for-japan')
    }, {
      value: contactPhone,
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Contact phone number', 'woocommerce-for-japan')
    }, {
      value: representativeLastName,
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Representative (last name)', 'woocommerce-for-japan')
    }, {
      value: representativeFirstName,
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Representative (first name)', 'woocommerce-for-japan')
    }, {
      value: representativeLastNameKana,
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Representative (last name kana)', 'woocommerce-for-japan')
    }, {
      value: representativeFirstNameKana,
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Representative (first name kana)', 'woocommerce-for-japan')
    }, {
      value: representativeDateOfBirth,
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Representative\'s date of birth (Gregorian calendar)', 'woocommerce-for-japan')
    }];
    requiredFields.forEach(field => {
      if (!field.value) {
        createErrorNotice(/* translators: %s: field label name */
        (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.sprintf)((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('%s is a required field. Please enter it.', 'woocommerce-for-japan'), field.label), {
          type: 'snackbar',
          isDismissible: true,
          autoDismiss: false
        });
        requiredFlag = true;
      }
    });
    let kanaFlag = false;
    const fullWidthKatakanaRegex = /^[ァ-ヶー]+$/;
    const requiredKanaFields = [{
      value: representativeLastNameKana,
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Representative (last name kana)', 'woocommerce-for-japan')
    }, {
      value: representativeFirstNameKana,
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Representative (first name kana)', 'woocommerce-for-japan')
    }];
    requiredKanaFields.forEach(field => {
      if (!fullWidthKatakanaRegex.test(field.value)) {
        createErrorNotice(/* translators: %s: field label name */
        (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.sprintf)((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('%s must be in full-width katakana. Please confirm.', 'woocommerce-for-japan'), field.label), {
          type: 'snackbar',
          isDismissible: true,
          autoDismiss: false
        });
        kanaFlag = true;
      }
    });
    let securityValidationFlag = false;
    if (securitySurvey01RadioControl === 'no' && !securitySurvey01TextControl?.trim()) {
      createErrorNotice((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('When selecting "No" for the security survey question, please provide additional details in the text field.', 'woocommerce-for-japan'), {
        type: 'snackbar',
        isDismissible: true,
        autoDismiss: false
      });
      securityValidationFlag = true;
    }
    const hasAnyYesCheck = securitySurvey11CheckControl === true || securitySurvey12CheckControl === true || securitySurvey13CheckControl === true || securitySurvey14CheckControl === true;
    if (!hasAnyYesCheck && !securitySurvey10TextAreaControl?.trim()) {
      createErrorNotice((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Since none of the security measures are implemented, please provide details about your security approach in the additional information field.', 'woocommerce-for-japan'), {
        type: 'snackbar',
        isDismissible: true,
        autoDismiss: false
      });
      securityValidationFlag = true;
    }
    if (requiredFlag) {
      createErrorNotice((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Please fill in all the fields.', 'woocommerce-for-japan'), {
        type: 'snackbar',
        isDismissible: true,
        autoDismiss: false
      });
      return;
    }
    if (kanaFlag || securityValidationFlag) {
      return;
    }
    const nextStep = currentStep + 1;
    setCurrentStep(nextStep);
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()({
      path: '/wp/v2/settings',
      method: 'POST',
      data: {
        woocommerce_paidy_on_boarding_settings: {
          currentStep: nextStep,
          storeName,
          siteName,
          storeUrl,
          registEmail,
          contactPhone,
          representativeLastName,
          representativeFirstName,
          representativeLastNameKana,
          representativeFirstNameKana,
          representativeDateOfBirth,
          annualGrossValue,
          averagePurchaseAmount,
          securitySurvey01RadioControl,
          securitySurvey01TextControl,
          securitySurvey11CheckControl,
          securitySurvey12CheckControl,
          securitySurvey13CheckControl,
          securitySurvey14CheckControl,
          securitySurvey10TextAreaControl,
          securitySurvey08RadioControl,
          securitySurvey09RadioControl
        }
      }
    }).then(() => {
      createSuccessNotice((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Settings saved.', 'woocommerce-for-japan'));
    });
  };
  return {
    currentStep,
    setCurrentStep,
    storeName,
    setStoreName,
    siteName,
    setSiteName,
    storeUrl,
    setStoreUrl,
    registEmail,
    setRegistEmail,
    contactPhone,
    setContactPhone,
    representativeLastName,
    setRepesentativeLastName,
    representativeFirstName,
    setRepresentativeFirstName,
    representativeLastNameKana,
    setRepresentativeLastNameKana,
    representativeFirstNameKana,
    setRepresentativeFirstNameKana,
    representativeDateOfBirth,
    setRepresentativeDateOfBirth,
    annualGrossValue,
    setAnnualGrossValue,
    averagePurchaseAmount,
    setAveragePurchaseAmount,
    securitySurvey01RadioControl,
    setSecuritySurvey01RadioControl,
    securitySurvey01TextControl,
    setSecuritySurvey01TextControl,
    securitySurvey11CheckControl,
    setSecuritySurvey11CheckControl,
    securitySurvey12CheckControl,
    setSecuritySurvey12CheckControl,
    securitySurvey13CheckControl,
    setSecuritySurvey13CheckControl,
    securitySurvey14CheckControl,
    setSecuritySurvey14CheckControl,
    securitySurvey10TextAreaControl,
    setSecuritySurvey10TextAreaControl,
    securitySurvey08RadioControl,
    setSecuritySurvey08RadioControl,
    securitySurvey09RadioControl,
    setSecuritySurvey09RadioControl,
    saveSettings
  };
};


/***/ }),

/***/ "./src/js/paidy/main-hooks/use-paidy-settings.jsx":
/*!********************************************************!*\
  !*** ./src/js/paidy/main-hooks/use-paidy-settings.jsx ***!
  \********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   usePaidySettings: () => (/* binding */ usePaidySettings)
/* harmony export */ });
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);


const usePaidySettings = () => {
  const [settingPaidy, setSettingPaidy] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)();
  const [errorMessage, setErrorMessage] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)();
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0___default()({
      path: '/wc/v3/payment_gateways/paidy'
    }).then(data => {
      setSettingPaidy(data);
    }).catch(err => {
      setErrorMessage(err.message);
    });
  });
  return {
    settingPaidy,
    setSettingPaidy,
    errorMessage
  };
};


/***/ }),

/***/ "./src/js/paidy/wizard/components/controls.jsx":
/*!*****************************************************!*\
  !*** ./src/js/paidy/wizard/components/controls.jsx ***!
  \*****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   AnnualGrossValueRadioControl: () => (/* binding */ AnnualGrossValueRadioControl),
/* harmony export */   AveragePurchaseAmountRadioControl: () => (/* binding */ AveragePurchaseAmountRadioControl),
/* harmony export */   ContactPhoneTextControl: () => (/* binding */ ContactPhoneTextControl),
/* harmony export */   RegistEmailTextControl: () => (/* binding */ RegistEmailTextControl),
/* harmony export */   RepresentativeDateOfBirthTextControl: () => (/* binding */ RepresentativeDateOfBirthTextControl),
/* harmony export */   RepresentativeFirstNameKanaTextControl: () => (/* binding */ RepresentativeFirstNameKanaTextControl),
/* harmony export */   RepresentativeFirstNameTextControl: () => (/* binding */ RepresentativeFirstNameTextControl),
/* harmony export */   RepresentativeLastNameKanaTextControl: () => (/* binding */ RepresentativeLastNameKanaTextControl),
/* harmony export */   RepresentativeLastNameTextControl: () => (/* binding */ RepresentativeLastNameTextControl),
/* harmony export */   SecuritySurvey01RadioControl: () => (/* binding */ SecuritySurvey01RadioControl),
/* harmony export */   SecuritySurvey01TextControl: () => (/* binding */ SecuritySurvey01TextControl),
/* harmony export */   SecuritySurvey08RadioControl: () => (/* binding */ SecuritySurvey08RadioControl),
/* harmony export */   SecuritySurvey09RadioControl: () => (/* binding */ SecuritySurvey09RadioControl),
/* harmony export */   SecuritySurvey10TextAreaControl: () => (/* binding */ SecuritySurvey10TextAreaControl),
/* harmony export */   SecuritySurvey11CheckControl: () => (/* binding */ SecuritySurvey11CheckControl),
/* harmony export */   SecuritySurvey12CheckControl: () => (/* binding */ SecuritySurvey12CheckControl),
/* harmony export */   SecuritySurvey13CheckControl: () => (/* binding */ SecuritySurvey13CheckControl),
/* harmony export */   SecuritySurvey14CheckControl: () => (/* binding */ SecuritySurvey14CheckControl),
/* harmony export */   SiteNameTextControl: () => (/* binding */ SiteNameTextControl),
/* harmony export */   StoreNameTextControl: () => (/* binding */ StoreNameTextControl),
/* harmony export */   StoreUrlTextControl: () => (/* binding */ StoreUrlTextControl)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! react/jsx-runtime */ "./node_modules/react/jsx-runtime.js");



const StoreNameTextControl = ({
  value,
  onChange
}) => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.TextControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Trade name / Store name', 'woocommerce-for-japan'),
    className: "paidy-store-name",
    type: "text",
    onChange: onChange,
    value: value
  });
};
const SiteNameTextControl = ({
  value,
  onChange
}) => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.TextControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Your EC site name', 'woocommerce-for-japan'),
    className: "paidy-site-name",
    type: "text",
    onChange: onChange,
    value: value
  });
};
const StoreUrlTextControl = ({
  value
}) => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("div", {
    className: "components-base-control",
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("label", {
      className: "components-base-control__label",
      children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Store URL', 'woocommerce-for-japan')
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("div", {
      className: "paidy-readonly-field",
      children: value
    })]
  });
};
const RegistEmailTextControl = ({
  value,
  onChange
}) => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.TextControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Email address for registering Paidy', 'woocommerce-for-japan'),
    value: value,
    onChange: onChange
  });
};
const ContactPhoneTextControl = ({
  value,
  onChange
}) => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.TextControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Contact phone number', 'woocommerce-for-japan'),
    type: "tel",
    onChange: onChange,
    value: value
  });
};
const RepresentativeLastNameTextControl = ({
  value,
  onChange
}) => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.TextControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Representative (last name)', 'woocommerce-for-japan'),
    onChange: onChange,
    value: value
  });
};
const RepresentativeFirstNameTextControl = ({
  value,
  onChange
}) => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.TextControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Representative (first name)', 'woocommerce-for-japan'),
    onChange: onChange,
    value: value
  });
};
const RepresentativeLastNameKanaTextControl = ({
  value,
  onChange
}) => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.TextControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Representative (last name kana)', 'woocommerce-for-japan'),
    help: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Please enter in full-width katakana.', 'woocommerce-for-japan'),
    value: value,
    onChange: onChange
  });
};
const RepresentativeFirstNameKanaTextControl = ({
  value,
  onChange
}) => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.TextControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Representative (first name kana)', 'woocommerce-for-japan'),
    help: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Please enter in full-width katakana.', 'woocommerce-for-japan'),
    value: value,
    onChange: onChange
  });
};
const RepresentativeDateOfBirthTextControl = ({
  value,
  onChange
}) => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.TextControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Representative\'s date of birth (Gregorian calendar)', 'woocommerce-for-japan'),
    help: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Please select a date from the calendar icon on the far right.', 'woocommerce-for-japan'),
    value: value,
    type: "date",
    onChange: onChange
  });
};
const AnnualGrossValueRadioControl = ({
  value,
  onChange
}) => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.RadioControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Annual gross merchandise value', 'woocommerce-for-japan'),
    selected: value,
    options: [{
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Less than 10 million yen', 'woocommerce-for-japan'),
      value: 'less-than-10-million-yen'
    }, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('10 million yen or more', 'woocommerce-for-japan'),
      value: '10-million-yen-or-more'
    }],
    onChange: onChange
  });
};
const AveragePurchaseAmountRadioControl = ({
  value,
  onChange
}) => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.RadioControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Average purchase amount per order', 'woocommerce-for-japan'),
    selected: value,
    options: [{
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Less than 50,000 yen', 'woocommerce-for-japan'),
      value: 'less-than-50000-yen'
    }, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Over 50,000 yen', 'woocommerce-for-japan'),
      value: '50000-yen-or-more'
    }],
    onChange: onChange
  });
};
const SecuritySurvey01RadioControl = ({
  value,
  onChange
}) => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.RadioControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Do you use two-step or two-factor authentication to prevent compromised accounts?', 'woocommerce-for-japan'),
    selected: value,
    onChange: onChange,
    options: [{
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Yes', 'woocommerce-for-japan'),
      value: 'yes'
    }, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('No', 'woocommerce-for-japan'),
      value: 'no'
    }, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Unknown', 'woocommerce-for-japan'),
      value: 'unknown'
    }]
  });
};
const SecuritySurvey01TextControl = ({
  value,
  onChange
}) => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.TextareaControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('If the answer is "no", please provide an alternative.', 'woocommerce-for-japan'),
    value: value,
    onChange: onChange
  });
};
const SecuritySurvey11CheckControl = ({
  value,
  onChange
}) => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.CheckboxControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Restricting access from suspicious IP addresses', 'woocommerce-for-japan'),
    checked: value,
    help: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('This refers to restricting access from suspicious IP addresses, such as those from overseas, using firewalls, WAFs, apps, etc.', 'woocommerce-for-japan'),
    onChange: onChange
  });
};
const SecuritySurvey12CheckControl = ({
  value,
  onChange
}) => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.CheckboxControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Identity verification using two-factor authentication, etc.', 'woocommerce-for-japan'),
    checked: value,
    help: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('This refers to increasing security strength by combining multiple levels or elements of authentication, such as biometric authentication using fingerprints or faces, or email or SMS authentication, in addition to ID and password.', 'woocommerce-for-japan'),
    onChange: onChange
  });
};
const SecuritySurvey13CheckControl = ({
  value,
  onChange
}) => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.CheckboxControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Fraud detection system (Fraud service)', 'woocommerce-for-japan'),
    checked: value,
    help: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('It is a security technology that constantly monitors communications on the network and detects fraudulent transactions in advance, such as the use of credit cards by third parties or impersonation.', 'woocommerce-for-japan'),
    onChange: onChange
  });
};
const SecuritySurvey14CheckControl = ({
  value,
  onChange
}) => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.CheckboxControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Device fingerprinting, etc.', 'woocommerce-for-japan'),
    help: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('This technology tracks online behavior by using the characteristics of the operating environment of the user\'s device as a kind of fingerprint.', 'woocommerce-for-japan'),
    checked: value,
    onChange: onChange
  });
};
const SecuritySurvey10TextAreaControl = ({
  value,
  onChange
}) => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.TextareaControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('If there is more than one question with a "yes" answer, please provide an alternative solution.', 'woocommerce-for-japan'),
    value: value,
    onChange: onChange
  });
};
const SecuritySurvey08RadioControl = ({
  value,
  onChange
}) => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.RadioControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Have you received any administrative disposition under the Specified Commercial Transactions Act in the past five years?', 'woocommerce-for-japan'),
    selected: value,
    onChange: onChange,
    options: [{
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('No', 'woocommerce-for-japan'),
      value: 'no'
    }, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Yes', 'woocommerce-for-japan'),
      value: 'yes'
    }]
  });
};
const SecuritySurvey09RadioControl = ({
  value,
  onChange
}) => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.RadioControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Have you ever been sued in a civil lawsuit for violating the Consumer Contract Act and lost the case?', 'woocommerce-for-japan'),
    selected: value,
    onChange: onChange,
    options: [{
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('No', 'woocommerce-for-japan'),
      value: 'no'
    }, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Yes', 'woocommerce-for-japan'),
      value: 'yes'
    }]
  });
};


/***/ }),

/***/ "./src/js/paidy/wizard/components/first-main-page.jsx":
/*!************************************************************!*\
  !*** ./src/js/paidy/wizard/components/first-main-page.jsx ***!
  \************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   FirstMainPage: () => (/* binding */ FirstMainPage)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _main_hooks_on_boarding_settings__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../main-hooks/on-boarding-settings */ "./src/js/paidy/main-hooks/on-boarding-settings.js");
/* harmony import */ var _main_hooks_use_paidy_settings__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../main-hooks/use-paidy-settings */ "./src/js/paidy/main-hooks/use-paidy-settings.jsx");
/* harmony import */ var _main_hooks_form_info__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../main-hooks/form-info */ "./src/js/paidy/main-hooks/form-info.jsx");
/* harmony import */ var _controls__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./controls */ "./src/js/paidy/wizard/components/controls.jsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! react/jsx-runtime */ "./node_modules/react/jsx-runtime.js");








const SettingsTitle = () => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.__experimentalHeading, {
    level: 1,
    children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Easy setup for Paidy payment', 'woocommerce-for-japan')
  });
};
const PaidyTitle = () => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.__experimentalHeading, {
    level: 2,
    children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Paidy application', 'woocommerce-for-japan')
  });
};
const ApplyButton = ({
  onClick
}) => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Button, {
    className: "paidy-go-apply paidy-button is-primary",
    onClick: onClick,
    children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Get started now', 'woocommerce-for-japan')
  });
};
const SaveButton = ({
  onClick
}) => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("div", {
    className: "paidy-save",
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Button, {
      className: "paidy-save paidy-button is-primary",
      onClick: onClick,
      children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('I agree to the above and apply', 'woocommerce-for-japan')
    })
  });
};
const FirstMainPage = () => {
  const {
    currentStep,
    setCurrentStep,
    storeName,
    setStoreName,
    siteName,
    setSiteName,
    storeUrl,
    registEmail,
    setRegistEmail,
    contactPhone,
    setContactPhone,
    representativeLastName,
    setRepesentativeLastName,
    representativeFirstName,
    setRepresentativeFirstName,
    representativeLastNameKana,
    setRepresentativeLastNameKana,
    representativeFirstNameKana,
    setRepresentativeFirstNameKana,
    representativeDateOfBirth,
    setRepresentativeDateOfBirth,
    annualGrossValue,
    setAnnualGrossValue,
    averagePurchaseAmount,
    setAveragePurchaseAmount,
    securitySurvey01RadioControl,
    setSecuritySurvey01RadioControl,
    securitySurvey01TextControl,
    setSecuritySurvey01TextControl,
    securitySurvey11CheckControl,
    setSecuritySurvey11CheckControl,
    securitySurvey12CheckControl,
    setSecuritySurvey12CheckControl,
    securitySurvey13CheckControl,
    setSecuritySurvey13CheckControl,
    securitySurvey14CheckControl,
    setSecuritySurvey14CheckControl,
    securitySurvey10TextAreaControl,
    setSecuritySurvey10TextAreaControl,
    securitySurvey08RadioControl,
    setSecuritySurvey08RadioControl,
    securitySurvey09RadioControl,
    setSecuritySurvey09RadioControl,
    saveSettings
  } = (0,_main_hooks_on_boarding_settings__WEBPACK_IMPORTED_MODULE_3__.useOnBoardingSettings)();
  const {
    settingPaidy,
    setSettingPaidy,
    errorMessage
  } = (0,_main_hooks_use_paidy_settings__WEBPACK_IMPORTED_MODULE_4__.usePaidySettings)();
  if (errorMessage) {
    console.debug(errorMessage);
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("div", {
      children: "loading..."
    });
  }
  const PaidyEnvironment = settingPaidy?.settings?.environment?.value || [];
  const paidyAdUrl = window.paidyForWcSettings?.paidyAdUrl || 'https://paidy.com/merchant/';
  const pluginName = window.paidyForWcSettings?.pluginName || 'Paidy for WooCommerce';
  if (currentStep === 1) {
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.Fragment, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(PaidyTitle, {}), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)("div", {
        id: "paidy-on-boarding",
        className: "paidy-on-boarding",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_main_hooks_form_info__WEBPACK_IMPORTED_MODULE_5__.FlowExplanation, {}), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("div", {
          className: "paidy-heading",
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.__experimentalHeading, {
            level: 3,
            children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Basic information', 'woocommerce-for-japan')
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)("div", {
          className: "paidy-basic-info",
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_controls__WEBPACK_IMPORTED_MODULE_6__.StoreNameTextControl, {
            value: storeName,
            onChange: value => setStoreName(value)
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_controls__WEBPACK_IMPORTED_MODULE_6__.SiteNameTextControl, {
            value: siteName,
            onChange: value => setSiteName(value)
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_controls__WEBPACK_IMPORTED_MODULE_6__.StoreUrlTextControl, {
            value: storeUrl
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_controls__WEBPACK_IMPORTED_MODULE_6__.RegistEmailTextControl, {
            value: registEmail,
            onChange: value => setRegistEmail(value)
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_controls__WEBPACK_IMPORTED_MODULE_6__.ContactPhoneTextControl, {
            value: contactPhone,
            onChange: value => setContactPhone(value)
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_controls__WEBPACK_IMPORTED_MODULE_6__.RepresentativeLastNameTextControl, {
            value: representativeLastName,
            onChange: value => setRepesentativeLastName(value)
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_controls__WEBPACK_IMPORTED_MODULE_6__.RepresentativeFirstNameTextControl, {
            value: representativeFirstName,
            onChange: value => setRepresentativeFirstName(value)
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_controls__WEBPACK_IMPORTED_MODULE_6__.RepresentativeLastNameKanaTextControl, {
            value: representativeLastNameKana,
            onChange: value => setRepresentativeLastNameKana(value)
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_controls__WEBPACK_IMPORTED_MODULE_6__.RepresentativeFirstNameKanaTextControl, {
            value: representativeFirstNameKana,
            onChange: value => setRepresentativeFirstNameKana(value)
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_controls__WEBPACK_IMPORTED_MODULE_6__.RepresentativeDateOfBirthTextControl, {
            value: representativeDateOfBirth,
            onChange: value => setRepresentativeDateOfBirth(value)
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_controls__WEBPACK_IMPORTED_MODULE_6__.AnnualGrossValueRadioControl, {
            value: annualGrossValue,
            onChange: value => setAnnualGrossValue(value)
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_controls__WEBPACK_IMPORTED_MODULE_6__.AveragePurchaseAmountRadioControl, {
            value: averagePurchaseAmount,
            onChange: value => setAveragePurchaseAmount(value)
          })]
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)("div", {
          className: "paidy-heading",
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.__experimentalHeading, {
            level: 3,
            children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Security survey', 'woocommerce-for-japan')
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("p", {
            children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Based on requests from the Ministry of Economy, Trade and Industry and the Japan Consumer Credit Association, all e-commerce affiliates are required to declare the status of their own security measures when joining. Please answer the following questions.', 'woocommerce-for-japan')
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("p", {
            children: (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createInterpolateElement)((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('For details, please refer to <link>the Security Measures Status Declaration Form FAQ.</link>', 'woocommerce-for-japan'), {
              link: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("a", {
                href: "https://paidy.com/merchant/faq/security-survey/",
                target: "_blank",
                rel: "noreferrer"
              })
            })
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("p", {
            children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('*If the system has not yet been built, please declare the check items for each security measure on the assumption that they will be satisfied once the system is built.', 'woocommerce-for-japan')
          })]
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)("div", {
          className: "paidy-security-survey",
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.__experimentalHeading, {
            level: 4,
            children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('[Measures to prevent leakage of cardholder data]', 'woocommerce-for-japan')
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("p", {
            children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Administrator screen access restrictions and administrator ID/PW management', 'woocommerce-for-japan')
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_controls__WEBPACK_IMPORTED_MODULE_6__.SecuritySurvey01RadioControl, {
            value: securitySurvey01RadioControl,
            onChange: value => setSecuritySurvey01RadioControl(value)
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_controls__WEBPACK_IMPORTED_MODULE_6__.SecuritySurvey01TextControl, {
            value: securitySurvey01TextControl,
            onChange: value => setSecuritySurvey01TextControl(value)
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("br", {}), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.__experimentalHeading, {
            level: 4,
            children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('[Countermeasures against unauthorized logins]', 'woocommerce-for-japan')
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)("p", {
            children: [(0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('*At least one "Yes" is required. If any are missing, we will contact you.', 'woocommerce-for-japan'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("br", {})]
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("p", {
            className: "unauthorized_logins_message",
            children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Do you take the following measures to prevent unauthorized logins when changing a user\'s attributes?', 'woocommerce-for-japan')
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_controls__WEBPACK_IMPORTED_MODULE_6__.SecuritySurvey11CheckControl, {
            value: securitySurvey11CheckControl,
            onChange: value => setSecuritySurvey11CheckControl(value)
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_controls__WEBPACK_IMPORTED_MODULE_6__.SecuritySurvey12CheckControl, {
            value: securitySurvey12CheckControl,
            onChange: value => setSecuritySurvey12CheckControl(value)
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_controls__WEBPACK_IMPORTED_MODULE_6__.SecuritySurvey13CheckControl, {
            value: securitySurvey13CheckControl,
            onChange: value => setSecuritySurvey13CheckControl(value)
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_controls__WEBPACK_IMPORTED_MODULE_6__.SecuritySurvey14CheckControl, {
            value: securitySurvey14CheckControl,
            onChange: value => setSecuritySurvey14CheckControl(value)
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_controls__WEBPACK_IMPORTED_MODULE_6__.SecuritySurvey10TextAreaControl, {
            value: securitySurvey10TextAreaControl,
            onChange: value => setSecuritySurvey10TextAreaControl(value)
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.__experimentalHeading, {
            level: 4,
            children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Questionnaire', 'woocommerce-for-japan')
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_controls__WEBPACK_IMPORTED_MODULE_6__.SecuritySurvey08RadioControl, {
            value: securitySurvey08RadioControl,
            onChange: value => setSecuritySurvey08RadioControl(value)
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_controls__WEBPACK_IMPORTED_MODULE_6__.SecuritySurvey09RadioControl, {
            value: securitySurvey09RadioControl,
            onChange: value => setSecuritySurvey09RadioControl(value)
          })]
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_main_hooks_form_info__WEBPACK_IMPORTED_MODULE_5__.AgreementInfo, {}), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(SaveButton, {
          onClick: saveSettings
        })]
      })]
    });
  } else if (currentStep === 2) {
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.Fragment, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(PaidyTitle, {}), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("div", {
        className: "paidy-on-boarding",
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_main_hooks_form_info__WEBPACK_IMPORTED_MODULE_5__.ApplyCompletedMessage, {})
      })]
    });
  } else if (currentStep === 3 && settingPaidy.enabled === true && PaidyEnvironment === 'sandbox') {
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.Fragment, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(PaidyTitle, {}), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("div", {
        className: "paidy-on-boarding",
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_main_hooks_form_info__WEBPACK_IMPORTED_MODULE_5__.SettingSandboxMessage, {})
      })]
    });
  } else if (currentStep === 3 && settingPaidy.enabled === true && PaidyEnvironment === 'live') {
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.Fragment, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(PaidyTitle, {}), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("div", {
        className: "paidy-on-boarding",
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_main_hooks_form_info__WEBPACK_IMPORTED_MODULE_5__.SettingCompletedMessage, {})
      })]
    });
  } else if (currentStep === 3) {
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.Fragment, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(PaidyTitle, {}), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("div", {
        className: "paidy-on-boarding",
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_main_hooks_form_info__WEBPACK_IMPORTED_MODULE_5__.ReviewApprovedMessage, {})
      })]
    });
  } else if (currentStep === 99) {
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.Fragment, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(PaidyTitle, {}), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("div", {
        className: "paidy-on-boarding",
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_main_hooks_form_info__WEBPACK_IMPORTED_MODULE_5__.ReviewRejectedMessage, {})
      })]
    });
  } else {
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.Fragment, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(SettingsTitle, {}), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("div", {
        className: "paidy-on-boarding__description",
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)("p", {
          children: [/* translators: %s: plugin name */
          (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.sprintf)((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Easy Paidy setup now available with %s!', 'woocommerce-for-japan'), pluginName), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("br", {}), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Right now, we are offering a one-month trial with no payment fees!', 'woocommerce-for-japan')]
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)("div", {
        className: "paidy-on-boarding",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("div", {
          className: "paidy-on-boarding__img",
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("a", {
            href: paidyAdUrl,
            target: "_blank",
            rel: "noreferrer",
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("img", {
              src: "/wp-content/plugins/paidy-wc/assets/images/paidy_logo_w800.png",
              alt: "Paidy"
            })
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("div", {
          className: "paidy-on-boarding__content",
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(ApplyButton, {
            onClick: () => setCurrentStep(1)
          })
        })]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)("div", {
        className: "paidy-on-boarding__description",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)("p", {
          className: "paidy-on-boarding__description-text",
          children: ["\u2666\uFE0F", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('What is Paidy?', 'woocommerce-for-japan'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("br", {}), "\u2705", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Paidy is a deferred payment service that can be used without the need for a credit card.', 'woocommerce-for-japan'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("br", {}), "\u2705", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Customers can make purchases with just their mobile phone number and email address.', 'woocommerce-for-japan'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("br", {}), "\u2705", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Payment can be made from the following month onwards via convenience store, bank or direct debit.', 'woocommerce-for-japan'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("br", {}), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('We respond to the desire to "buy now."', 'woocommerce-for-japan'), " ", /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("a", {
            href: paidyAdUrl,
            target: "_blank",
            rel: "noreferrer",
            children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Learn more about Paidy', 'woocommerce-for-japan')
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("br", {})]
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)("p", {
          className: "paidy-on-boarding__description-text",
          children: ["\u2666\uFE0F", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Benefits of implementation (for EC businesses)', 'woocommerce-for-japan'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("br", {}), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('No credit card registration is required, making it easy for new customers to make purchases with confidence.', 'woocommerce-for-japan'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("br", {}), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Installment payments are also supported, which is expected to increase the success rate of high-priced products.', 'woocommerce-for-japan'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("br", {}), "(", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('*Free installment fees only apply when paying by direct debit or bank transfer. Identity verification and app download required.', 'woocommerce-for-japan'), ")", /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("br", {}), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Currently used on over 700,000 websites nationwide, including Amazon. Industry-leading track record.', 'woocommerce-for-japan'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("br", {})]
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)("p", {
          className: "paidy-on-boarding__description-text",
          children: ["\u2666\uFE0F", /* translators: %s: plugin name */
          (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.sprintf)((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('%s * Paidy', 'woocommerce-for-japan'), pluginName), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("br", {}), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('With this update, Paidy can now be installed in as little as 30 seconds!', 'woocommerce-for-japan'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("br", {}), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('No initial or monthly fees.', 'woocommerce-for-japan'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("br", {}), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('The review process is completed in as little as 1-2 days, and setup is easy.', 'woocommerce-for-japan'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("br", {})]
        })]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)("div", {
        className: "paidy-on-boarding_bottom",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("div", {
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(ApplyButton, {
            onClick: () => setCurrentStep(1)
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("div", {
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("a", {
            href: "/wp-admin/admin.php?page=wc-settings&tab=checkout",
            children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('pass this time', 'woocommerce-for-japan')
          })
        })]
      })]
    });
  }
};


/***/ }),

/***/ "./src/js/paidy/wizard/index.scss":
/*!****************************************!*\
  !*** ./src/js/paidy/wizard/index.scss ***!
  \****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "@wordpress/api-fetch":
/*!**********************************!*\
  !*** external ["wp","apiFetch"] ***!
  \**********************************/
/***/ ((module) => {

module.exports = window["wp"]["apiFetch"];

/***/ }),

/***/ "@wordpress/components":
/*!************************************!*\
  !*** external ["wp","components"] ***!
  \************************************/
/***/ ((module) => {

module.exports = window["wp"]["components"];

/***/ }),

/***/ "@wordpress/data":
/*!******************************!*\
  !*** external ["wp","data"] ***!
  \******************************/
/***/ ((module) => {

module.exports = window["wp"]["data"];

/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/***/ ((module) => {

module.exports = window["wp"]["element"];

/***/ }),

/***/ "@wordpress/hooks":
/*!*******************************!*\
  !*** external ["wp","hooks"] ***!
  \*******************************/
/***/ ((module) => {

module.exports = window["wp"]["hooks"];

/***/ }),

/***/ "@wordpress/i18n":
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
/***/ ((module) => {

module.exports = window["wp"]["i18n"];

/***/ }),

/***/ "@wordpress/notices":
/*!*********************************!*\
  !*** external ["wp","notices"] ***!
  \*********************************/
/***/ ((module) => {

module.exports = window["wp"]["notices"];

/***/ }),

/***/ "react":
/*!************************!*\
  !*** external "React" ***!
  \************************/
/***/ ((module) => {

module.exports = window["React"];

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!**************************************!*\
  !*** ./src/js/paidy/wizard/index.js ***!
  \**************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/hooks */ "@wordpress/hooks");
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _components_first_main_page__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./components/first-main-page */ "./src/js/paidy/wizard/components/first-main-page.jsx");
/* harmony import */ var _index_scss__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./index.scss */ "./src/js/paidy/wizard/index.scss");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! react/jsx-runtime */ "./node_modules/react/jsx-runtime.js");





const BackAdminFooter = () => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("div", {
    className: "paidy-on-boarding footer",
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("a", {
      href: "/wp-admin/admin.php?page=wc-settings&tab=checkout&section=paidy",
      children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Back to Paidy settings', 'woocommerce-for-japan')
    })
  });
};
const PaidyOnBoardingPage = () => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.Fragment, {
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_components_first_main_page__WEBPACK_IMPORTED_MODULE_2__.FirstMainPage, {}), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(BackAdminFooter, {})]
  });
};
(0,_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__.addFilter)('woocommerce_admin_pages_list', 'paidy-on-boarding', pages => {
  pages.push({
    container: PaidyOnBoardingPage,
    path: '/paidy-on-boarding',
    breadcrumbs: [(0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Easy setup for Paidy payment', 'woocommerce-for-japan')],
    capability: 'manage_woocommerce'
  });
  return pages;
});
})();

/******/ })()
;
//# sourceMappingURL=paidy.js.map