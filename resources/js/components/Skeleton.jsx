/**
 * src/components/Skeleton.jsx
 * Loading skeleton components.
 */
export function ProductCardSkeleton() {
  return (
    <div className="bg-card-bg border border-border-subtle rounded-xl overflow-hidden flex flex-col animate-pulse">
      <div className="aspect-square bg-surface-container" />
      <div className="p-3 flex flex-col gap-2">
        <div className="h-2.5 bg-surface-container rounded w-1/3" />
        <div className="h-3.5 bg-surface-container rounded w-full" />
        <div className="h-3.5 bg-surface-container rounded w-3/4" />
        <div className="flex items-center justify-between mt-2">
          <div className="h-5 bg-surface-container rounded w-1/2" />
          <div className="w-9 h-9 bg-surface-container rounded-lg" />
        </div>
      </div>
    </div>
  );
}

export function ProductGridSkeleton({ count = 12 }) {
  return (
    <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 lg:gap-4">
      {Array.from({ length: count }).map((_, i) => (
        <ProductCardSkeleton key={i} />
      ))}
    </div>
  );
}

export function ComplexDashboardSkeleton() {
  return (
    <div className="w-full bg-white border border-gray-100 rounded-2xl p-6 flex flex-col gap-6 shadow-sm overflow-hidden animate-pulse">
      {/* Top Header Row */}
      <div className="flex justify-between items-center w-full">
        {/* Left Title Placeholder */}
        <div className="h-6 w-48 sm:w-64 bg-[#f1f3f5] rounded-full" />
        
        {/* Right Actions Placeholder */}
        <div className="flex items-center gap-3">
          <div className="h-6 w-32 bg-[#f1f3f5] rounded-full hidden sm:block" />
          <div className="h-4 w-16 bg-[#f1f3f5] rounded-full" />
        </div>
      </div>

      {/* Middle Content Row */}
      <div className="flex flex-col md:flex-row gap-6 w-full">
        
        {/* Left Side: 4 Square Blocks */}
        <div className="flex gap-4 w-full md:w-[45%] flex-shrink-0">
          {[1, 2, 3, 4].map(i => (
            <div key={i} className="aspect-square w-1/4 bg-[#f1f3f5] rounded-xl" />
          ))}
        </div>
        
        {/* Right Side: Data Table Area */}
        <div className="w-full md:flex-1 relative border border-[#f1f3f5] rounded-xl flex flex-col">
          {/* Header row (small pills) */}
          <div className="flex justify-between border-b border-[#f1f3f5] p-3 pr-10">
            {[1, 2, 3, 4, 5, 6, 7].map(i => (
               <div key={i} className="h-2 w-8 bg-[#f1f3f5] rounded-full" />
            ))}
          </div>
          
          {/* Data Columns */}
          <div className="flex justify-between gap-4 p-4 pr-10 w-full">
            {[1, 2, 3].map(col => (
              <div key={col} className="flex flex-col gap-2.5 w-1/3">
                <div className="h-2 w-12 sm:w-16 bg-[#f1f3f5] rounded-full" />
                <div className="h-10 w-full bg-[#f1f3f5] rounded-lg" />
              </div>
            ))}
          </div>
          
          {/* 3-dot Menu Icon on the far right */}
          <div className="absolute right-0 top-0 bottom-0 w-10 border-l border-[#f1f3f5] flex flex-col items-center justify-start pt-3 gap-1">
            <div className="w-1 h-1 bg-gray-300 rounded-full" />
            <div className="w-1 h-1 bg-gray-300 rounded-full" />
            <div className="w-1 h-1 bg-gray-300 rounded-full" />
          </div>
        </div>
        
      </div>

      {/* Bottom Footer Row */}
      <div className="w-full h-8 bg-[#f1f3f5] rounded-full mt-2" />
    </div>
  );
}
