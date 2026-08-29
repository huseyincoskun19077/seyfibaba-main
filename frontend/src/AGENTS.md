# FRONTEND SRC

**Generated:** 2026-04-15
**Parent:** ../../AGENTS.md

## OVERVIEW
Next.js 15 frontend with React, Redux Toolkit, Tailwind CSS.

## STRUCTURE
```
src/
├── app/           # Next.js 15 App Router
├── components/   # 41 reusable components
├── redux/         # RTK store, slices
├── hooks/         # Custom React hooks
├── utils/        # Utility functions
├── api/          # API client
├── lib/          # Libraries
├── data/         # Static data
└── assets/      # Static assets
```

## WHERE TO LOOK
| Task | Location |
|------|----------|
| Pages | src/app/ |
| Components | src/components/ |
| State | src/redux/ |
| API | src/utils/api.js |
| Auth | src/utils/auth.js |

## CONVENTIONS
- Components: Functional + hooks
- State: Redux Toolkit
- Styling: Tailwind CSS
- API: Axios with interceptors

## ANTI-PATTERNS
- DO NOT use class components
- DO NOT use this.state
