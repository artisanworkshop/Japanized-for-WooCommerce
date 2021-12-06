
export interface IWindowMessage {
	event: string;
	message?: string;
}

export interface IWindowFetchMessage<T> {
	event: string;
	request: T;
}

/**
 * Helper function to give strong typing to a Message Event
 * */
export function ppOnWindowMessage<T extends IWindowMessage>(eventName: string, cb: (event: T) => Promise<void> | void) {
	self.addEventListener('message', async (event: MessageEvent<T>) => {
		if (event.data.event === eventName) {
			await cb(event.data);
		}
	}, false);
}

/**
 * Allows for async request from the host page for data.
 */
export function onWindowDataFetch<TRequest, TResponse>(endpoint: string, requestCallback: (request: TRequest) => Promise<TResponse> | TResponse): void {
	self.addEventListener('message', async (message: MessageEvent<IWindowFetchMessage<TRequest>>) => {
		if (message.data.event === endpoint) {
			try {
				const response = await requestCallback(message.data.request as unknown as TRequest);

				message.ports[0].postMessage({result: response});
			} catch (error: unknown) {
				message.ports[0].postMessage({error});
			}
		}
	});
}

export function ppIframeWindow(): Window | null {
	return document.querySelector<HTMLIFrameElement>('#peachpay-iframe')?.contentWindow ?? null;
}

export async function ppFetchIframeData<TRequest, TResponse>(endpoint: string, request: TRequest): Promise<TResponse> {
	return ppFetchWindowData(ppIframeWindow(), endpoint, request);
}

export async function ppFetchWindowData<TRequest, TResponse>(targetWindow: Window | null, endpoint: string, request: TRequest): Promise<TResponse> {
	return new Promise((resolve, reject) => {
		// Reject if response takes longer then 15 seconds.
		const timeoutId = setTimeout(() => {
			reject(new Error(`Window Fetch timed out for ${endpoint}.`));
		}, 15_000);

		const channel = new MessageChannel();
		channel.port1.onmessage = ({data}) => {
			channel.port1.close();

			clearTimeout(timeoutId);
			if (data.error) {
				reject(data.error);
			} else {
				resolve(data.result);
			}
		};

		if (!targetWindow) {
			clearTimeout(timeoutId);
			reject('Target window is not valid.');
		} else {
			targetWindow.postMessage({
				event: endpoint,
				request: request,
			}, '*', [channel.port2]);
		}
	});
}
