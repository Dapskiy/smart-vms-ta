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
    if len(sys.argv) < 4:
        print("Usage: python generate_tts.py <text_file> <voice> <output_mp3>")
        sys.exit(1)

    text_file = sys.argv[1]
    voice = sys.argv[2]
    output_path = sys.argv[3]

    with open(text_file, 'r', encoding='utf-8') as f:
        text = f.read()

    communicate = edge_tts.Communicate(text, voice)
    await communicate.save(output_path)

if __name__ == '__main__':
    asyncio.run(main())
