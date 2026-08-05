import sys
import asyncio
import edge_tts

# On Windows, set event loop policy to avoid WinError 10106 with Proactor
if sys.platform == 'win32':
    try:
        asyncio.set_event_loop_policy(asyncio.WindowsSelectorEventLoopPolicy())
    except Exception:
        pass

async def main():
    if len(sys.argv) < 3:
        print("Usage: python generate_tts.py <text_file> <voice>")
        sys.exit(1)

    text_file = sys.argv[1]
    voice = sys.argv[2]

    with open(text_file, 'r', encoding='utf-8') as f:
        text = f.read()

    # Rate increased to +20% for faster reading as requested
    communicate = edge_tts.Communicate(text, voice, rate="+20%")
    
    # Stream audio data to stdout for instant playback (0-second delay)
    async for chunk in communicate.stream():
        if chunk["type"] == "audio":
            sys.stdout.buffer.write(chunk["data"])
            sys.stdout.buffer.flush()

if __name__ == '__main__':
    asyncio.run(main())
