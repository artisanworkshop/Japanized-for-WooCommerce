/**
 * Security Settings Component
 *
 * The result of the security check (issue #259): the score of the four
 * requirements of the Credit Card Security Guidelines, the items that need
 * action, and a re-check, which runs in WP-Cron while this tab waits for it.
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	PanelBody,
	PanelRow,
	ToggleControl,
	Button,
	Notice,
	Spinner,
	TextControl,
	TextareaControl,
} from '@wordpress/components';
import {
	useState,
	useEffect,
	useRef,
	createInterpolateElement,
} from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { dateI18n, getSettings } from '@wordpress/date';

import {
	SEVERITY_ORDER,
	REQUIREMENT_ORDER,
	ratingSeverity,
	requirementLabels,
	severityLabels,
	checkLabels,
} from './security-labels';

/**
 * How often the tab asks for the result while a re-check runs, and for how long.
 */
const POLL_INTERVAL = 5000;
const POLL_TIMEOUT = 180000;

/**
 * Credit line the Wordfence Intelligence terms ask for, shown as is.
 */
const ATTRIBUTION = 'Vulnerability data: Wordfence Intelligence';

/**
 * Terms of use of wc.artws.info, which cover the vulnerability feed.
 */
const TERMS_URL = 'https://wc.artws.info/security-assessment-term/';

/**
 * The limits of the login attempt limiter: setting key, label, default and range
 * (the same ranges as JP4WC_Login_Limiter::LIMITS, which the server applies).
 */
const loginLimitFields = () => [
	{
		key: 'login-limit-ip-attempts',
		label: __( 'Failures allowed per IP address', 'woocommerce-for-japan' ),
		help: __(
			'Behind a proxy or CDN that hides the visitor address, every visitor shares one address: keep this one the looser limit.',
			'woocommerce-for-japan'
		),
		fallback: '10',
		min: 1,
		max: 100,
	},
	{
		key: 'login-limit-user-attempts',
		label: __( 'Failures allowed per user name', 'woocommerce-for-japan' ),
		help: __(
			'An e-mail address used to log in counts against the same name as the user name.',
			'woocommerce-for-japan'
		),
		fallback: '5',
		min: 1,
		max: 100,
	},
	{
		key: 'login-limit-lockout-minutes',
		label: __( 'Lockout time (minutes)', 'woocommerce-for-japan' ),
		help: __(
			'A failure counts for this long. Logins are refused while the failures that still count reach a limit.',
			'woocommerce-for-japan'
		),
		fallback: '15',
		min: 1,
		max: 1440,
	},
];

/**
 * Returns the text that says what else protects the login page.
 *
 * @param {Object|null} protectedBy login_limit.protected_by of the findings view.
 * @return {string} Text, empty when nothing else protects it.
 */
const protectedByText = ( protectedBy ) => {
	if ( ! protectedBy ) {
		return '';
	}
	switch ( protectedBy.type ) {
		case 'plugin':
			return sprintf(
				/* translators: %s: plugin slug */
				__(
					'The plugin "%s" already protects the login page, so this limiter does nothing while it is active.',
					'woocommerce-for-japan'
				),
				protectedBy.name
			);
		case 'jetpack':
			return __(
				"Jetpack's brute force protection already protects the login page, so this limiter does nothing while it is on.",
				'woocommerce-for-japan'
			);
		default:
			return __(
				'The server configuration (.htaccess) already restricts the login page, so this limiter does nothing while it does.',
				'woocommerce-for-japan'
			);
	}
};

/**
 * Formats a time for display in the site's date and time format.
 *
 * @param {number|string} value Unix time in seconds, or an ISO 8601 string.
 * @return {string} Formatted time.
 */
const formatTime = ( value ) =>
	dateI18n(
		getSettings().formats.datetime,
		typeof value === 'number' ? new Date( value * 1000 ) : value
	);

/**
 * Returns the rows of the items that need action, most severe first: one row
 * per failed check, and for V-01 one row per vulnerability record found.
 *
 * @param {Object} view Result of GET /jp4wc/v1/security/findings.
 * @return {Array<Object>} Rows.
 */
const findingRows = ( view ) => {
	const rows = [];
	view.checks.forEach( ( check ) => {
		if ( check.status !== 'fail' ) {
			return;
		}
		if ( check.check_id === 'V-01' ) {
			const before = rows.length;
			( view.vulnerabilities?.items || [] ).forEach( ( item, index ) => {
				item.vulnerabilities.forEach( ( record ) => {
					rows.push( {
						key: `V-01:${ index }:${ record.id }`,
						severity: ratingSeverity( record.rating ),
						item,
						record,
					} );
				} );
			} );
			if ( rows.length > before ) {
				return;
			}
		}
		rows.push( {
			key: check.check_id,
			severity: check.severity,
			check,
		} );
	} );

	// Array.prototype.sort() is stable, so the order of the checks stays within a severity.
	const rank = ( severity ) => {
		const index = SEVERITY_ORDER.indexOf( severity );
		return index === -1 ? SEVERITY_ORDER.length : index;
	};
	return rows.sort( ( a, b ) => rank( a.severity ) - rank( b.severity ) );
};

/**
 * Returns how to fix a vulnerability record.
 *
 * @param {Object} item   Affected software.
 * @param {Object} record Vulnerability record.
 * @return {string} Text.
 */
const recordFix = ( item, record ) => {
	if ( ! record.patched ) {
		if ( item.type === 'core' ) {
			return __(
				'No fixed version yet. Update WordPress as soon as its security release is out.',
				'woocommerce-for-japan'
			);
		}
		return __(
			'No fixed version yet. Deactivate and delete it, or replace it with another plugin or theme.',
			'woocommerce-for-japan'
		);
	}
	if ( record.patched_versions.length === 0 ) {
		return __(
			'Update it to the latest version.',
			'woocommerce-for-japan'
		);
	}
	return sprintf(
		/* translators: 1: plugin, theme or WordPress name, 2: comma-separated versions that fix the vulnerability */
		__(
			'Update %1$s to a fixed version (%2$s) or later.',
			'woocommerce-for-japan'
		),
		item.name,
		record.patched_versions.join( ', ' )
	);
};

/**
 * Returns the label of a status of a requirement or a check.
 *
 * @param {string} status pass, action or fail, unknown.
 * @return {string} Label.
 */
const statusLabel = ( status ) => {
	switch ( status ) {
		case 'pass':
			return __( 'Satisfied', 'woocommerce-for-japan' );
		case 'action':
		case 'fail':
			return __( 'Action needed', 'woocommerce-for-japan' );
		default:
			return __( 'Unconfirmed', 'woocommerce-for-japan' );
	}
};

/**
 * The total score and the cards of the four requirements.
 *
 * @param {Object} props      Props.
 * @param {Object} props.view Result of the security check.
 * @return {Element} Element.
 */
const Scorecard = ( { view } ) => {
	const names = requirementLabels();
	return (
		<div className="jp4wc-security-scorecard">
			<div className="jp4wc-security-total">
				<span className="jp4wc-security-total-label">
					{ __( 'Total score', 'woocommerce-for-japan' ) }
				</span>
				<span className="jp4wc-security-total-score">
					{ view.score.total }
					<small> / 100</small>
				</span>
				<span className="jp4wc-security-total-note">
					{ __(
						'The lowest score of the four requirements.',
						'woocommerce-for-japan'
					) }
				</span>
			</div>
			<ul className="jp4wc-security-requirements">
				{ REQUIREMENT_ORDER.map( ( key ) => {
					const requirement = view.requirements[ key ];
					if ( ! requirement ) {
						return null;
					}
					return (
						<li
							key={ key }
							className={ `jp4wc-security-requirement is-${ requirement.status }` }
						>
							<span className="jp4wc-security-requirement-name">
								{ names[ key ] }
							</span>
							<span className="jp4wc-security-requirement-score">
								{ requirement.score }
								<small> / 100</small>
							</span>
							<span
								className={ `jp4wc-security-badge is-${ requirement.status }` }
							>
								{ statusLabel( requirement.status ) }
							</span>
							<span className="jp4wc-security-requirement-counts">
								{ sprintf(
									/* translators: 1: checks passed, 2: checks that need action, 3: checks that could not be run */
									__(
										'Passed %1$d / Action needed %2$d / Unconfirmed %3$d',
										'woocommerce-for-japan'
									),
									requirement.pass,
									requirement.fail,
									requirement.unknown
								) }
							</span>
						</li>
					);
				} ) }
			</ul>
		</div>
	);
};

/**
 * The table of the items that need action.
 *
 * @param {Object} props      Props.
 * @param {Object} props.view Result of the security check.
 * @return {Element} Element.
 */
const FindingsTable = ( { view } ) => {
	const rows = findingRows( view );
	const checks = checkLabels();
	const severities = severityLabels();
	const unknown = view.checks.some( ( check ) => check.status === 'unknown' );

	if ( rows.length === 0 ) {
		return (
			<p className="jp4wc-security-no-findings">
				{ __( 'No items need action.', 'woocommerce-for-japan' ) }
				{ unknown &&
					' ' +
						__(
							'Some checks could not be run (see "All checks" below).',
							'woocommerce-for-japan'
						) }
			</p>
		);
	}

	return (
		<div className="jp4wc-security-findings-wrap">
			<table className="widefat striped jp4wc-security-findings">
				<thead>
					<tr>
						<th scope="col">
							{ __( 'Severity', 'woocommerce-for-japan' ) }
						</th>
						<th scope="col">
							{ __( 'Item', 'woocommerce-for-japan' ) }
						</th>
						<th scope="col">
							{ __( 'Fixed version', 'woocommerce-for-japan' ) }
						</th>
						<th scope="col">
							{ __( 'How to fix', 'woocommerce-for-japan' ) }
						</th>
						<th scope="col">
							{ __( 'Reference', 'woocommerce-for-japan' ) }
						</th>
					</tr>
				</thead>
				<tbody>
					{ rows.map( ( row ) => (
						<tr key={ row.key }>
							<td>
								<span
									className={ `jp4wc-security-severity is-${ row.severity }` }
								>
									{ severities[ row.severity ] ||
										row.severity }
								</span>
							</td>
							{ row.record ? (
								<>
									<td>
										<a href={ row.item.manage_url }>
											{ row.item.name }
										</a>{ ' ' }
										{ row.item.version }
										<br />
										<span className="description">
											{ row.record.title }
										</span>
									</td>
									<td>
										{ row.record.patched
											? row.record.patched_versions.join(
													', '
											  ) || '—'
											: __(
													'None',
													'woocommerce-for-japan'
											  ) }
									</td>
									<td>
										{ recordFix( row.item, row.record ) }
									</td>
									<td>
										{ /* The data license asks for a link to the record wherever it is shown. */ }
										<a
											href={ row.record.reference }
											target="_blank"
											rel="noopener noreferrer"
										>
											{ __(
												'Details',
												'woocommerce-for-japan'
											) }
										</a>
									</td>
								</>
							) : (
								<>
									<td>
										{ checks[ row.check.check_id ]?.title ||
											row.check.check_id }
									</td>
									<td>—</td>
									<td>
										{ checks[ row.check.check_id ]?.fix ||
											'' }
									</td>
									<td>—</td>
								</>
							) }
						</tr>
					) ) }
				</tbody>
			</table>
		</div>
	);
};

/**
 * Every check with its status, by requirement.
 *
 * @param {Object} props      Props.
 * @param {Object} props.view Result of the security check.
 * @return {Element} Element.
 */
const AllChecks = ( { view } ) => {
	const names = requirementLabels();
	const checks = checkLabels();
	return (
		<PanelBody
			title={ __( 'All checks', 'woocommerce-for-japan' ) }
			initialOpen={ false }
		>
			{ REQUIREMENT_ORDER.map( ( key ) => {
				const list = view.checks.filter(
					( check ) => check.requirement === key
				);
				if ( list.length === 0 ) {
					return null;
				}
				return (
					<div key={ key } className="jp4wc-security-check-group">
						<h4>{ names[ key ] }</h4>
						<ul className="jp4wc-security-checks">
							{ list.map( ( check ) => (
								<li key={ check.check_id }>
									<span
										className={ `jp4wc-security-badge is-${ check.status }` }
									>
										{ check.status === 'pass'
											? __(
													'Passed',
													'woocommerce-for-japan'
											  )
											: statusLabel( check.status ) }
									</span>{ ' ' }
									{ checks[ check.check_id ]?.title ||
										check.check_id }
								</li>
							) ) }
						</ul>
					</div>
				);
			} ) }
			<p className="description">
				{ __(
					'"Unconfirmed" means the check could not be run on this site, for example because the server cannot send requests to itself. It costs no points.',
					'woocommerce-for-japan'
				) }
			</p>
		</PanelBody>
	);
};

/**
 * What the tab says about the vulnerability data.
 *
 * @param {Object} props      Props.
 * @param {Object} props.view Result of the security check.
 * @return {Element|null} Element.
 */
const FeedStatus = ( { view } ) => {
	if ( view.feed.unavailable ) {
		return (
			<Notice status="warning" isDismissible={ false }>
				{ sprintf(
					/* translators: %s: host name of the vulnerability feed, e.g. wc.artws.info */
					__(
						'The vulnerability data could not be downloaded for several days, so the installed software is not checked for known vulnerabilities. Make sure this server can connect to %s.',
						'woocommerce-for-japan'
					),
					view.feed.host
				) }
			</Notice>
		);
	}
	if ( ! view.vulnerabilities ) {
		return (
			<Notice status="info" isDismissible={ false }>
				{ __(
					'The vulnerability data has not been downloaded yet, so known vulnerabilities are not checked. It is downloaded once a day.',
					'woocommerce-for-japan'
				) }
			</Notice>
		);
	}
	if ( view.vulnerabilities.stale ) {
		return (
			<Notice status="warning" isDismissible={ false }>
				{ __(
					'The vulnerability data has not been updated for a while, so vulnerabilities disclosed since then are not checked.',
					'woocommerce-for-japan'
				) }
			</Notice>
		);
	}
	return null;
};

/**
 * The credit line and the copyright notices of the vulnerability data, as its license asks.
 *
 * @param {Object} props      Props.
 * @param {Object} props.view Result of the security check, or null.
 * @return {Element} Element.
 */
const Credits = ( { view } ) => (
	<div className="jp4wc-security-credits">
		<p className="description">{ ATTRIBUTION }</p>
		{ ( view?.vulnerabilities?.copyrights || [] ).map( ( copyright ) => (
			<details key={ copyright.notice }>
				<summary>{ copyright.notice }</summary>
				<p>{ copyright.license }</p>
				<p>
					<a
						href={ copyright.license_url }
						target="_blank"
						rel="noopener noreferrer"
					>
						{ __( 'License', 'woocommerce-for-japan' ) }
					</a>
				</p>
			</details>
		) ) }
	</div>
);

/**
 * Returns the entries of an allow list as typed: one per line, commas and spaces also separate.
 *
 * @param {string} value Allow list.
 * @return {string[]} Entries.
 */
const allowEntries = ( value ) =>
	( typeof value === 'string' ? value : '' )
		.split( /[\s,]+/ )
		.filter( ( entry ) => entry !== '' );

/**
 * Returns who added an XML-RPC method, as the usage list shows it.
 *
 * @param {Object} provider      Provider.
 * @param {string} provider.type core, plugin, mu-plugin, theme or unknown.
 * @param {string} provider.name Name.
 * @return {string} Text.
 */
const providerText = ( provider ) => {
	switch ( provider?.type ) {
		case 'core':
			return 'WordPress';
		case 'plugin':
			return provider.name;
		case 'mu-plugin':
			return sprintf(
				/* translators: %s: file or directory of a must-use plugin */
				__( '%s (must-use plugin)', 'woocommerce-for-japan' ),
				provider.name
			);
		case 'theme':
			return sprintf(
				/* translators: %s: theme name */
				__( '%s (theme)', 'woocommerce-for-japan' ),
				provider.name
			);
		default:
			return __( 'Unknown source', 'woocommerce-for-japan' );
	}
};

/**
 * Returns what turning XML-RPC off means for a recorded method.
 *
 * @param {Object}  entry   Recorded method.
 * @param {boolean} enabled Whether XML-RPC is turned off.
 * @return {string} Text.
 */
const usageStatus = ( entry, enabled ) => {
	if ( entry.refusable === false ) {
		return __(
			'Answered whatever the setting (no login)',
			'woocommerce-for-japan'
		);
	}
	if ( entry.calls > 0 && entry.outcomes.jetpack === entry.calls ) {
		return __(
			'Jetpack: keeps working when XML-RPC is off',
			'woocommerce-for-japan'
		);
	}
	if ( entry.allowed ) {
		return __( 'Allowed', 'woocommerce-for-japan' );
	}
	if ( enabled && entry.outcomes.refused > 0 ) {
		return __( 'Refused', 'woocommerce-for-japan' );
	}
	if ( entry.outcomes.login > 0 ) {
		return __(
			'Logs in: stops when XML-RPC is off unless allowed',
			'woocommerce-for-japan'
		);
	}
	if ( entry.outcomes.failed > 0 ) {
		return __(
			'Only failed logins: likely password guessing',
			'woocommerce-for-japan'
		);
	}
	return __( 'No login', 'woocommerce-for-japan' );
};

/**
 * Headings of the XML-RPC usage list.
 *
 * @return {string[]} Headings.
 */
const usageHeadings = () => [
	__( 'Method', 'woocommerce-for-japan' ),
	__( 'Added by', 'woocommerce-for-japan' ),
	__( 'Calls', 'woocommerce-for-japan' ),
	__( 'Logins (worked / failed)', 'woocommerce-for-japan' ),
	__( 'Last call', 'woocommerce-for-japan' ),
	__( 'Client', 'woocommerce-for-japan' ),
	__( 'Status', 'woocommerce-for-japan' ),
];

/**
 * One method of the XML-RPC usage list.
 *
 * @param {Object}   props         Props.
 * @param {Object}   props.entry   Recorded method.
 * @param {boolean}  props.enabled Whether XML-RPC is turned off.
 * @param {boolean}  props.listed  Whether the allow list as edited has the method.
 * @param {Function} props.onAllow Adds a method to the allow list.
 * @return {Element} Element.
 */
const UsageRow = ( { entry, enabled, listed, onAllow } ) => {
	const onlyJetpack =
		entry.calls > 0 && entry.outcomes.jetpack === entry.calls;
	const canAllow =
		entry.refusable !== false && ! entry.allowed && ! onlyJetpack;
	// The client of the last login that worked is the integration; failed attempts cannot hide it.
	const client = entry.login_user_agent || entry.user_agent || '—';
	const label = listed
		? __( 'Added', 'woocommerce-for-japan' )
		: __( 'Allow', 'woocommerce-for-japan' );
	return (
		<tr>
			<td>
				<code>{ entry.method }</code>
			</td>
			<td>{ providerText( entry.provider ) }</td>
			<td>{ entry.calls }</td>
			<td>{ `${ entry.outcomes.login } / ${ entry.outcomes.failed }` }</td>
			<td>{ formatTime( entry.last_at ) }</td>
			<td className="jp4wc-xmlrpc-client">{ client }</td>
			<td>{ usageStatus( entry, enabled ) }</td>
			<td>
				{ canAllow && (
					<Button
						variant="secondary"
						size="small"
						onClick={ () => onAllow( entry.method ) }
						disabled={ listed }
					>
						{ label }
					</Button>
				) }
			</td>
		</tr>
	);
};

/**
 * The XML-RPC calls of the last 30 days, with a button that adds a method to the allow list.
 *
 * @param {Object}   props           Props.
 * @param {Object}   props.xmlrpc    The xmlrpc part of the result.
 * @param {string[]} props.allowList Entries of the allow list as edited.
 * @param {Function} props.onAllow   Adds a method to the allow list.
 * @return {Element} Element.
 */
const XmlrpcUsage = ( { xmlrpc, allowList, onAllow } ) => {
	const usage = xmlrpc.usage || [];
	return (
		<PanelBody
			title={ __(
				'XML-RPC usage (last 30 days)',
				'woocommerce-for-japan'
			) }
			initialOpen={ usage.length > 0 }
		>
			<p className="description">
				{ __(
					'The XML-RPC calls this site answered, by method. Methods that log in are used by an integration (the WordPress mobile apps, an offline editor, a service): allow them before turning XML-RPC off. Methods with only failed logins are usually password guessing.',
					'woocommerce-for-japan'
				) }
			</p>
			{ ! xmlrpc.readable && (
				<Notice status="error" isDismissible={ false }>
					{ __(
						'The record of the XML-RPC calls could not be read.',
						'woocommerce-for-japan'
					) }
				</Notice>
			) }
			{ xmlrpc.readable && usage.length === 0 && (
				<p>
					{ __(
						'No XML-RPC calls were recorded in the last 30 days.',
						'woocommerce-for-japan'
					) }
				</p>
			) }
			{ usage.length > 0 && (
				<div className="jp4wc-security-findings-wrap">
					<table className="widefat striped jp4wc-security-findings jp4wc-xmlrpc-usage">
						<thead>
							<tr>
								{ usageHeadings().map( ( heading ) => (
									<th key={ heading }>{ heading }</th>
								) ) }
								<th>
									<span className="screen-reader-text">
										{ __(
											'Allow list',
											'woocommerce-for-japan'
										) }
									</span>
								</th>
							</tr>
						</thead>
						<tbody>
							{ usage.map( ( entry ) => (
								<UsageRow
									key={ entry.method }
									entry={ entry }
									enabled={ xmlrpc.enabled }
									listed={ allowList.includes(
										entry.method
									) }
									onAllow={ onAllow }
								/>
							) ) }
						</tbody>
					</table>
				</div>
			) }
		</PanelBody>
	);
};

const SecuritySettings = ( {
	settings,
	updateSetting,
	saveSettings,
	saving,
} ) => {
	const [ view, setView ] = useState( null );
	const [ loading, setLoading ] = useState( true );
	const [ loadError, setLoadError ] = useState( null );
	// idle, waiting (for the re-check), done, timeout or error.
	const [ recheck, setRecheck ] = useState( { state: 'idle' } );
	const pollTimer = useRef( null );
	const mounted = useRef( true );

	const loadView = async () => {
		const response = await apiFetch( {
			path: '/jp4wc/v1/security/findings',
			method: 'GET',
		} );
		if ( mounted.current ) {
			setView( response );
		}
		return response;
	};

	// Asks for the result until the re-check asked for at requestedAt has run.
	const waitForRecheck = ( requestedAt, startedAt ) => {
		clearTimeout( pollTimer.current );
		pollTimer.current = setTimeout( async () => {
			try {
				const response = await loadView();
				if ( ! mounted.current ) {
					return;
				}
				if ( ! response.enabled ) {
					setRecheck( { state: 'idle' } );
					return;
				}
				if ( response.recheck.done_at >= requestedAt ) {
					setRecheck( { state: 'done' } );
					return;
				}
			} catch ( err ) {
				// Ask again on the next round.
			}
			if ( ! mounted.current ) {
				return;
			}
			if ( Date.now() - startedAt >= POLL_TIMEOUT ) {
				setRecheck( { state: 'timeout' } );
				return;
			}
			waitForRecheck( requestedAt, startedAt );
		}, POLL_INTERVAL );
	};

	useEffect( () => {
		mounted.current = true;
		loadView()
			.then( ( response ) => {
				if ( mounted.current && response.recheck?.pending ) {
					setRecheck( { state: 'waiting' } );
					waitForRecheck( response.recheck.requested_at, Date.now() );
				}
			} )
			.catch( ( err ) => {
				if ( mounted.current ) {
					setLoadError(
						err.message ||
							__(
								'The result of the security check could not be loaded.',
								'woocommerce-for-japan'
							)
					);
				}
			} )
			.finally( () => {
				if ( mounted.current ) {
					setLoading( false );
				}
			} );
		return () => {
			mounted.current = false;
			clearTimeout( pollTimer.current );
		};
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	const startRecheck = async () => {
		setRecheck( { state: 'waiting' } );
		try {
			const response = await apiFetch( {
				path: '/jp4wc/v1/security/rescan',
				method: 'POST',
			} );
			if ( ! mounted.current ) {
				return;
			}
			setView( response );
			waitForRecheck( response.recheck.requested_at, Date.now() );
		} catch ( err ) {
			if ( mounted.current ) {
				setRecheck( {
					state: 'error',
					message:
						err.message ||
						__(
							'The re-check could not be started.',
							'woocommerce-for-japan'
						),
				} );
			}
		}
	};

	const handleSave = async () => {
		await saveSettings( settings );
		// Turning the check off removes its results; turning it on schedules a check.
		try {
			await loadView();
		} catch ( err ) {
			// The previous result stays on screen.
		}
	};

	const enabled = view?.enabled;
	const loginLimitOn = settings?.[ 'login-limit' ] === '1';
	const protectedBy = protectedByText( view?.login_limit?.protected_by );
	const fileEdit = view?.hardening?.file_edit;
	let fileEditElsewhere = '';
	if ( fileEdit?.defined_elsewhere ) {
		fileEditElsewhere = __(
			'File editing is already forbidden by wp-config.php or another plugin (DISALLOW_FILE_EDIT or DISALLOW_FILE_MODS), so this setting changes nothing while that stays.',
			'woocommerce-for-japan'
		);
	} else if ( fileEdit?.allowed_by_constant ) {
		fileEditElsewhere = __(
			'wp-config.php defines DISALLOW_FILE_EDIT as false, so this setting cannot forbid file editing until that line is removed.',
			'woocommerce-for-japan'
		);
	}
	const xmlrpcOff = settings?.[ 'disable-xmlrpc' ] === '1';
	const allowList = allowEntries( settings?.[ 'xmlrpc-allow' ] );
	const addToAllowList = ( method ) => {
		if ( ! allowList.includes( method ) ) {
			updateSetting(
				'xmlrpc-allow',
				[ ...allowList, method ].join( '\n' )
			);
		}
	};
	// Allowed methods still accept a password: say so while nothing limits login attempts through XML-RPC.
	const allowedUnprotected =
		xmlrpcOff &&
		allowList.length > 0 &&
		view?.xmlrpc?.login_protected === false;
	let allowedNote = '';
	if ( allowedUnprotected && view?.login_limit?.protected_by ) {
		// The limiter sleeps behind what protects the login page, which does not cover xmlrpc.php.
		allowedNote = __(
			'The allowed methods still accept a password, and what protects the login page does not cover XML-RPC (the login attempt limiter stays asleep behind it): use a security plugin that limits login attempts, or leave the allow list empty.',
			'woocommerce-for-japan'
		);
	} else if ( allowedUnprotected ) {
		allowedNote = __(
			'The allowed methods still accept a password, and nothing limits login attempts through XML-RPC: turn on the login attempt limiter or use a security plugin that limits login attempts.',
			'woocommerce-for-japan'
		);
	}
	// Saved as on, nothing else protects the page, yet not running: the constant or a filter.
	const disabledOutside =
		view?.login_limit?.enabled === true &&
		view?.login_limit?.active === false &&
		! view?.login_limit?.protected_by
			? __(
					'The limiter is turned off by JP4WC_LOGIN_LIMIT_DISABLE in wp-config.php or by a filter.',
					'woocommerce-for-japan'
			  )
			: '';

	return (
		<div className="jp4wc-security-settings">
			{ loading && (
				<div className="jp4wc-security-loading">
					<Spinner />
				</div>
			) }

			{ loadError && (
				<Notice status="error" isDismissible={ false }>
					{ loadError }
				</Notice>
			) }

			{ view && ! enabled && (
				<Notice status="info" isDismissible={ false }>
					{ __(
						'The security check is off. Turn it on below and save the settings to check this site.',
						'woocommerce-for-japan'
					) }
				</Notice>
			) }

			{ view && enabled && (
				<>
					<FeedStatus view={ view } />

					{ view.requirements ? (
						<>
							<Scorecard view={ view } />
							<p className="jp4wc-security-disclaimer description">
								{ __(
									'This check shows what needs attention. It does not guarantee compliance with the Credit Card Security Guidelines.',
									'woocommerce-for-japan'
								) }
							</p>
						</>
					) : (
						<Notice status="info" isDismissible={ false }>
							{ __(
								'This site has not been checked yet. The first check runs shortly in WP-Cron, or start it with "Re-check now".',
								'woocommerce-for-japan'
							) }
						</Notice>
					) }

					<div className="jp4wc-security-recheck">
						<Button
							variant="secondary"
							onClick={ startRecheck }
							isBusy={ recheck.state === 'waiting' }
							disabled={ recheck.state === 'waiting' }
						>
							{ recheck.state === 'waiting'
								? __( 'Re-checking…', 'woocommerce-for-japan' )
								: __(
										'Re-check now',
										'woocommerce-for-japan'
								  ) }
						</Button>
						<span className="description">
							{ view.checked_at > 0 &&
								sprintf(
									/* translators: %s: date and time of the last check */
									__(
										'Last checked: %s',
										'woocommerce-for-japan'
									),
									formatTime( view.checked_at )
								) }
							{ view.vulnerabilities?.generated_at &&
								' / ' +
									sprintf(
										/* translators: %s: date and time the vulnerability data was generated */
										__(
											'Vulnerability data: %s',
											'woocommerce-for-japan'
										),
										formatTime(
											view.vulnerabilities.generated_at
										)
									) }
						</span>
					</div>

					{ recheck.state === 'waiting' && (
						<Notice status="info" isDismissible={ false }>
							{ __(
								'The re-check runs in the background. The results appear here when it finishes.',
								'woocommerce-for-japan'
							) }
						</Notice>
					) }
					{ recheck.state === 'done' && (
						<Notice
							status="success"
							onRemove={ () => setRecheck( { state: 'idle' } ) }
						>
							{ __(
								'The re-check has finished.',
								'woocommerce-for-japan'
							) }
						</Notice>
					) }
					{ recheck.state === 'timeout' && (
						<Notice
							status="warning"
							onRemove={ () => setRecheck( { state: 'idle' } ) }
						>
							{ __(
								'The re-check has not finished yet. It runs with WP-Cron, so reload this page later to see the results.',
								'woocommerce-for-japan'
							) }
						</Notice>
					) }
					{ recheck.state === 'error' && (
						<Notice
							status="error"
							onRemove={ () => setRecheck( { state: 'idle' } ) }
						>
							{ recheck.message }
						</Notice>
					) }

					{ view.requirements && (
						<>
							<h3>
								{ __(
									'Items that need action',
									'woocommerce-for-japan'
								) }
							</h3>
							<FindingsTable view={ view } />
							<AllChecks view={ view } />
						</>
					) }
				</>
			) }

			{ view?.xmlrpc && (
				<XmlrpcUsage
					xmlrpc={ view.xmlrpc }
					allowList={ allowList }
					onAllow={ addToAllowList }
				/>
			) }

			<PanelBody
				title={ __( 'Settings', 'woocommerce-for-japan' ) }
				initialOpen={ true }
			>
				<PanelRow>
					<ToggleControl
						__nextHasNoMarginBottom={ true }
						label={ __(
							'Security check',
							'woocommerce-for-japan'
						) }
						help={
							__(
								'Once a day, download the list of known vulnerabilities from wc.artws.info (Artisan Workshop) and check the installed plugins, themes and WordPress against it. Only the versions of this plugin, WordPress, WooCommerce and PHP are sent, in the User-Agent header.',
								'woocommerce-for-japan'
							) +
							' ' +
							__(
								'Turning it off also stops the checks on this tab and deletes their results.',
								'woocommerce-for-japan'
							)
						}
						checked={ settings?.[ 'security-scan' ] === '1' }
						onChange={ ( value ) =>
							updateSetting( 'security-scan', value ? '1' : '' )
						}
					/>
				</PanelRow>
				<PanelRow>
					<ToggleControl
						__nextHasNoMarginBottom={ true }
						label={ __(
							'Login attempt limiter',
							'woocommerce-for-japan'
						) }
						help={
							__(
								'Count failed logins per IP address and per user name, and refuse logins for a while once a limit is reached. Covers the WordPress login page, the WooCommerce My Account and checkout login forms and XML-RPC.',
								'woocommerce-for-japan'
							) +
							' ' +
							__(
								'It does nothing while a security plugin, Jetpack or the server configuration already protects the login page. If you lock yourself out, define JP4WC_LOGIN_LIMIT_DISABLE as true in wp-config.php.',
								'woocommerce-for-japan'
							) +
							( protectedBy ? ' ' + protectedBy : '' ) +
							( disabledOutside ? ' ' + disabledOutside : '' )
						}
						checked={ loginLimitOn }
						onChange={ ( value ) =>
							updateSetting( 'login-limit', value ? '1' : '' )
						}
					/>
				</PanelRow>
				{ loginLimitOn &&
					loginLimitFields().map( ( field ) => (
						<PanelRow key={ field.key }>
							<TextControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom={ true }
								type="number"
								label={ field.label }
								help={ field.help }
								value={
									settings?.[ field.key ] ?? field.fallback
								}
								onChange={ ( value ) =>
									updateSetting( field.key, value )
								}
								min={ field.min }
								max={ field.max }
								disabled={ Boolean( protectedBy ) }
							/>
						</PanelRow>
					) ) }
				<PanelRow>
					<ToggleControl
						__nextHasNoMarginBottom={ true }
						label={ __(
							'Hide the user list from the REST API',
							'woocommerce-for-japan'
						) }
						help={ __(
							'Do not answer the users REST route (/wp-json/wp/v2/users) for visitors who are not logged in. Logged-in users keep their WordPress permissions (a browser tab opened on the route without a nonce counts as a visitor). Turn it off if a public page of this site lists authors through the REST API.',
							'woocommerce-for-japan'
						) }
						checked={ settings?.[ 'hide-rest-users' ] === '1' }
						onChange={ ( value ) =>
							updateSetting( 'hide-rest-users', value ? '1' : '' )
						}
					/>
				</PanelRow>
				<PanelRow>
					<ToggleControl
						__nextHasNoMarginBottom={ true }
						label={ __(
							'Forbid file editing in the admin screens',
							'woocommerce-for-japan'
						) }
						help={
							__(
								'Define DISALLOW_FILE_EDIT when the plugin loads, so the theme and plugin file editors are gone from the admin screens. Updates and installs still work.',
								'woocommerce-for-japan'
							) +
							( fileEditElsewhere ? ' ' + fileEditElsewhere : '' )
						}
						checked={ settings?.[ 'disallow-file-edit' ] === '1' }
						onChange={ ( value ) =>
							updateSetting(
								'disallow-file-edit',
								value ? '1' : ''
							)
						}
					/>
				</PanelRow>
				<PanelRow>
					<ToggleControl
						__nextHasNoMarginBottom={ true }
						label={ __(
							'Disable XML-RPC',
							'woocommerce-for-japan'
						) }
						help={ __(
							'Refuse every XML-RPC method except the allowed ones, so that no password can be tried and no pingback sent through XML-RPC. Jetpack keeps working: it checks its own signature. The WordPress mobile apps, offline editors and services that log in through XML-RPC stop unless their methods are allowed. The REST API, with the application passwords used for it, is not affected.',
							'woocommerce-for-japan'
						) }
						checked={ xmlrpcOff }
						onChange={ ( value ) =>
							updateSetting( 'disable-xmlrpc', value ? '1' : '' )
						}
					/>
				</PanelRow>
				{ xmlrpcOff && (
					<PanelRow>
						<TextareaControl
							__nextHasNoMarginBottom={ true }
							label={ __(
								'Allowed methods',
								'woocommerce-for-japan'
							) }
							help={
								__(
									'One method per line, such as wp.getUsersBlogs. A name ending in * allows every method that starts with it, such as metaWeblog.*. "Allow" in the XML-RPC usage list above adds a method. Leave it empty to refuse every method.',
									'woocommerce-for-japan'
								) + ( allowedNote ? ' ' + allowedNote : '' )
							}
							value={ settings?.[ 'xmlrpc-allow' ] ?? '' }
							onChange={ ( value ) =>
								updateSetting( 'xmlrpc-allow', value )
							}
							rows={ 4 }
						/>
					</PanelRow>
				) }
				<PanelRow>
					<p className="description">
						{ createInterpolateElement(
							__(
								'The vulnerability data comes from wc.artws.info and is subject to its <a>Terms of Use</a>.',
								'woocommerce-for-japan'
							),
							{
								a: (
									// eslint-disable-next-line jsx-a11y/anchor-has-content -- createInterpolateElement() puts the translated words inside.
									<a
										href={ TERMS_URL }
										target="_blank"
										rel="noopener noreferrer"
									/>
								),
							}
						) }
					</p>
				</PanelRow>
			</PanelBody>

			<div className="jp4wc-settings-footer">
				<Button
					variant="primary"
					onClick={ handleSave }
					isBusy={ saving }
					disabled={ saving }
				>
					{ saving
						? // eslint-disable-next-line @wordpress/i18n-ellipsis -- The string of the other tabs.
						  __( 'Saving...', 'woocommerce-for-japan' )
						: __( 'Save Settings', 'woocommerce-for-japan' ) }
				</Button>
			</div>

			<Credits view={ view } />
		</div>
	);
};

export default SecuritySettings;
