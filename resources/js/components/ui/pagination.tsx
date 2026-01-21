import * as React from "react"
import {
  ChevronLeftIcon,
  ChevronRightIcon,
  MoreHorizontalIcon,
} from "lucide-react"
import { Link } from "@inertiajs/react"

import { cn } from "@/lib/utils"
import { buttonVariants } from "@/components/ui/button"

interface PaginationProps extends React.ComponentProps<"nav"> {
  currentPage: number
  lastPage: number
  queryParams?: Record<string, string>
}

function Pagination({ currentPage, lastPage, queryParams = {}, className, ...props }: PaginationProps) {
  const pages = Array.from({ length: lastPage }, (_, i) => i + 1)
  const showPages = pages.filter(page => {
    if (page === 1 || page === lastPage) return true
    if (page >= currentPage - 1 && page <= currentPage + 1) return true
    return false
  })

  const getUrl = (page: number) => {
    const params = new URLSearchParams(queryParams)
    params.set('page', page.toString())
    return `?${params.toString()}`
  }

  return (
    <nav
      role="navigation"
      aria-label="pagination"
      data-slot="pagination"
      className={cn("mx-auto flex w-full justify-center", className)}
      {...props}
    >
      <ul className="flex flex-row items-center gap-1">
        {currentPage > 1 && (
          <li>
            <Link
              href={getUrl(currentPage - 1)}
              className={cn(
                buttonVariants({ variant: "ghost" }),
                "gap-1 px-2.5"
              )}
              preserveScroll
            >
              <ChevronLeftIcon className="h-4 w-4" />
              <span>Previous</span>
            </Link>
          </li>
        )}

        {showPages.map((page, index) => {
          const isGap = index > 0 && page - showPages[index - 1] > 1

          return (
            <React.Fragment key={page}>
              {isGap && (
                <li>
                  <span className="flex h-9 w-9 items-center justify-center">
                    <MoreHorizontalIcon className="h-4 w-4" />
                  </span>
                </li>
              )}
              <li>
                <Link
                  href={getUrl(page)}
                  className={cn(
                    buttonVariants({
                      variant: page === currentPage ? "outline" : "ghost",
                    }),
                    "h-9 w-9"
                  )}
                  preserveScroll
                >
                  {page}
                </Link>
              </li>
            </React.Fragment>
          )
        })}

        {currentPage < lastPage && (
          <li>
            <Link
              href={getUrl(currentPage + 1)}
              className={cn(
                buttonVariants({ variant: "ghost" }),
                "gap-1 px-2.5"
              )}
              preserveScroll
            >
              <span>Next</span>
              <ChevronRightIcon className="h-4 w-4" />
            </Link>
          </li>
        )}
      </ul>
    </nav>
  )
}

export { Pagination }
